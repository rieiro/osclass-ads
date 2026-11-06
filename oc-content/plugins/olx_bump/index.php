<?php
/*
Plugin Name: OLX Ad Promotions & Bump
Plugin URI: https://github.com/osclass/
Description: OLX-style ad promotions: 1-click bump-to-top (republish), visual highlighted cards, and featured ribbons.
Version: 1.0.1
Author: Advanced Agentic Engineer
Author URI: https://osclass.org/
Short Name: olx_bump
Plugin update URI:
*/

function olx_bump_install() {
    $conn = DBConnectionClass::newInstance();
    $data = $conn->getOsclassDb();
    $comm = new DBCommandClass($data);

    $comm->query("CREATE TABLE IF NOT EXISTS " . DB_TABLE_PREFIX . "t_olx_promotions (
        fk_i_item_id INT UNSIGNED NOT NULL,
        b_highlighted TINYINT(1) NOT NULL DEFAULT 0,
        dt_highlight_until DATETIME NULL,
        i_bumps_count INT UNSIGNED NOT NULL DEFAULT 0,
        dt_last_bump DATETIME NULL,
        PRIMARY KEY (fk_i_item_id)
    ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;");

    osc_set_preference('bump_cooldown_hours', '24', 'olx_bump', 'INTEGER');
    osc_set_preference('highlight_days', '7', 'olx_bump', 'INTEGER');
}

function olx_bump_uninstall() {
    $conn = DBConnectionClass::newInstance();
    $data = $conn->getOsclassDb();
    $comm = new DBCommandClass($data);

    $comm->query("DROP TABLE IF EXISTS " . DB_TABLE_PREFIX . "t_olx_promotions");
    Preference::newInstance()->delete(array('s_section' => 'olx_bump'));
}

// Add CSS styling for highlighted and featured ads
function olx_bump_header() {
    echo '<style>
        .olx-highlighted-card { background: #fffdf5 !important; border: 2px solid #f59e0b !important; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.15) !important; position: relative; }
        .olx-featured-badge { position: absolute; top: 10px; right: 10px; background: #ea580c; color: #fff; font-size: 11px; font-weight: 800; text-transform: uppercase; padding: 3px 8px; border-radius: 4px; letter-spacing: 0.5px; z-index: 2; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .olx-bump-btn { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; font-size: 12px; font-weight: 600; border-radius: 4px; background: #0284c7; color: #fff !important; text-decoration: none !important; transition: background 0.15s; margin-left: 6px; }
        .olx-bump-btn:hover { background: #0369a1; }
        .olx-highlight-btn { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; font-size: 12px; font-weight: 600; border-radius: 4px; background: #f59e0b; color: #fff !important; text-decoration: none !important; transition: background 0.15s; margin-left: 6px; }
        .olx-highlight-btn:hover { background: #d97706; }
    </style>';
}

// Helper to check if item is promoted
function olx_is_item_highlighted($item_id) {
    if ($item_id <= 0) return false;
    static $cache = array();
    if (!defined("OC_TEST_MODE") && isset($cache[$item_id])) return $cache[$item_id];

    $dao = new DAO();
    $dao->udb = DBConnectionClass::newInstance()->getOsclassDb();
    $dao->dao = new DBCommandClass($dao->udb);
    $dao->dao->select('b_highlighted, dt_highlight_until');
    $dao->dao->from(DB_TABLE_PREFIX . 't_olx_promotions');
    $dao->dao->where('fk_i_item_id', $item_id);
    $res = $dao->dao->get();

    $is_highlighted = false;
    if ($res && $res->numRows() > 0) {
        $row = $res->row();
        if ((int)$row['b_highlighted'] == 1 && (empty($row['dt_highlight_until']) || strtotime($row['dt_highlight_until']) > time())) {
            $is_highlighted = true;
        }
    }
    $cache[$item_id] = $is_highlighted;
    return $is_highlighted;
}

// Hook into loop card class
function olx_bump_card_class() {
    $item_id = osc_item_id();
    if (olx_is_item_highlighted($item_id)) {
        echo 'olx-highlighted-card ';
    }
}

// Inject "Featured" ribbon and loop badge
function olx_bump_card_top() {
    $item_id = osc_item_id();
    if (olx_is_item_highlighted($item_id)) {
        echo '<span class="olx-featured-badge">&#9733; Featured</span>';
    }
}

// Inject Bump & Highlight management buttons on user listings (user-items.php)
function olx_bump_user_items_actions($item_id = null) {
    if (!$item_id) {
        $item_id = osc_item_id();
    }
    if ($item_id <= 0) return;

    $bump_url = osc_base_url(true) . '?page=custom&action=olx_do_bump&item_id=' . $item_id;
    $highlight_url = osc_base_url(true) . '?page=custom&action=olx_do_highlight&item_id=' . $item_id;

    echo ' | <a href="' . osc_esc_html($bump_url) . '" class="olx-bump-btn" title="Bump this listing to the top of search results"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 19V5M5 12l7-7 7 7"/></svg> Subir Anúncio (Bump)</a>';
    echo '<a href="' . osc_esc_html($highlight_url) . '" class="olx-highlight-btn" title="Highlight listing with standout badge and colors"><svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg> Destaque (Highlight)</a>';
}

// Controller action for performing Bump & Highlight
function olx_bump_custom_controller() {
    $action = Params::getParam('action');
    if ($action !== 'olx_do_bump' && $action !== 'olx_do_highlight') {
        return;
    }

    if (!osc_is_web_user_logged_in()) {
        osc_add_flash_error_message(__('You must be logged in to manage your listings.', 'olx_bump'));
        header('Location: ' . osc_user_login_url());
        exit;
    }

    $item_id = (int)Params::getParam('item_id');
    $user_id = osc_logged_user_id();

    $item = Item::newInstance()->findByPrimaryKey($item_id);
    if (!$item || (int)$item['fk_i_user_id'] !== $user_id) {
        osc_add_flash_error_message(__('Listing not found or access denied.', 'olx_bump'));
        header('Location: ' . osc_user_list_items_url());
        exit;
    }

    $dao = new DAO();
    $dao->udb = DBConnectionClass::newInstance()->getOsclassDb();
    $dao->dao = new DBCommandClass($dao->udb);
    $now = date('Y-m-d H:i:s');

    if ($action === 'olx_do_bump') {
        Item::newInstance()->update(array('dt_pub_date' => $now, 'dt_mod_date' => $now), array('pk_i_id' => $item_id));

        $dao->dao->query("INSERT INTO " . DB_TABLE_PREFIX . "t_olx_promotions (fk_i_item_id, i_bumps_count, dt_last_bump)
            VALUES ({$item_id}, 1, '{$now}')
            ON DUPLICATE KEY UPDATE i_bumps_count = i_bumps_count + 1, dt_last_bump = '{$now}'");

        osc_add_flash_ok_message(__('Listing has been bumped to the top of search results!', 'olx_bump'));
    }

    if ($action === 'olx_do_highlight') {
        $days = (int)osc_get_preference('highlight_days', 'olx_bump') ?: 7;
        $until = date('Y-m-d H:i:s', strtotime("+{$days} days"));

        $dao->dao->query("INSERT INTO " . DB_TABLE_PREFIX . "t_olx_promotions (fk_i_item_id, b_highlighted, dt_highlight_until)
            VALUES ({$item_id}, 1, '{$until}')
            ON DUPLICATE KEY UPDATE b_highlighted = 1, dt_highlight_until = '{$until}'");

        Item::newInstance()->update(array('b_premium' => 1), array('pk_i_id' => $item_id));

        osc_add_flash_ok_message(__('Listing is now highlighted as a Featured Ad!', 'olx_bump'));
    }

    header('Location: ' . osc_user_list_items_url());
    exit;
}

// Hook registrations
osc_register_plugin(osc_plugin_path(__FILE__), 'olx_bump_install');
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'olx_bump_uninstall');

osc_add_hook('header', 'olx_bump_header');
osc_add_hook('highlight_class', 'olx_bump_card_class');
osc_add_hook('item_loop_top', 'olx_bump_card_top');
osc_add_hook('user_items_body', 'olx_bump_user_items_actions');
osc_add_hook('init', 'olx_bump_custom_controller');
