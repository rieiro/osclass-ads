<?php
/*
Plugin Name: OLX Pro Seller Storefronts
Plugin URI: https://github.com/osclass/
Description: Branded digital storefronts for dealerships and business sellers with custom banner, logo, business bio, and dedicated inventory catalog.
Version: 1.0.2
Author: Advanced Agentic Engineer
Author URI: https://osclass.org/
Short Name: olx_stores
Plugin update URI:
*/

function olx_stores_install() {
    $conn = DBConnectionClass::newInstance();
    $data = $conn->getOsclassDb();
    $comm = new DBCommandClass($data);

    $comm->query("CREATE TABLE IF NOT EXISTS " . DB_TABLE_PREFIX . "t_olx_stores (
        fk_i_user_id INT UNSIGNED NOT NULL,
        s_slug VARCHAR(100) NOT NULL,
        s_store_name VARCHAR(150) NOT NULL,
        s_tagline VARCHAR(255) NULL,
        s_bio TEXT NULL,
        s_banner_url VARCHAR(255) NULL,
        s_logo_url VARCHAR(255) NULL,
        s_phone VARCHAR(50) NULL,
        s_whatsapp VARCHAR(50) NULL,
        s_address VARCHAR(255) NULL,
        dt_created_at DATETIME NOT NULL,
        PRIMARY KEY (fk_i_user_id),
        UNIQUE KEY uq_slug (s_slug)
    ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;");
}

function olx_stores_uninstall() {
    $conn = DBConnectionClass::newInstance();
    $data = $conn->getOsclassDb();
    $comm = new DBCommandClass($data);
    $comm->query("DROP TABLE IF EXISTS " . DB_TABLE_PREFIX . "t_olx_stores");
}

function olx_stores_user_menu() {
    if (osc_is_web_user_logged_in()) {
        $store_url = osc_render_file_url('olx_stores/manage_store.php');
        echo '<li class="opt_olx_store"><a href="' . osc_esc_html($store_url) . '">My Pro Storefront</a></li>';
    }
}

function olx_stores_router() {
    $slug = Params::getParam('store_slug');
    if (!empty($slug)) {
        require_once osc_plugins_path() . 'olx_stores/public_store.php';
        exit;
    }
}

function olx_stores_item_detail() {
    $user_id = osc_item_user_id();
    if ($user_id > 0) {
        $dao = new DAO();
        $dao->udb = DBConnectionClass::newInstance()->getOsclassDb();
        $dao->dao = new DBCommandClass($dao->udb);
        $dao->dao->select('s_slug, s_store_name');
        $dao->dao->from(DB_TABLE_PREFIX . 't_olx_stores');
        $dao->dao->where('fk_i_user_id', $user_id);
        $res = $dao->dao->get();
        if ($res && $res->numRows() > 0) {
            $store = $res->row();
            $store_link = osc_base_url(true) . '?store_slug=' . urlencode($store['s_slug']);
            echo '<div style="margin: 15px 0; padding: 14px 18px; background: #faf5ff; border: 1.5px solid #d8b4fe; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">';
            echo '<div><div style="font-size: 11px; text-transform: uppercase; font-weight: 800; color: #7e22ce; letter-spacing: 0.5px;">Official Verified Store</div>';
            echo '<div style="font-weight: 700; color: #1e1b4b; font-size: 16px;">' . osc_esc_html($store['s_store_name']) . '</div></div>';
            echo '<a href="' . osc_esc_html($store_link) . '" style="background: #7e22ce; color: #fff; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-weight: bold; font-size: 13px;">View Full Showroom &rarr;</a>';
            echo '</div>';
        }
    }
}

osc_register_plugin(osc_plugin_path(__FILE__), 'olx_stores_install');
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'olx_stores_uninstall');
osc_add_hook('user_menu', 'olx_stores_user_menu');
osc_add_hook('init', 'olx_stores_router');
osc_add_hook('item_detail', 'olx_stores_item_detail');
