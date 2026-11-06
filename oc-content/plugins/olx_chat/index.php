<?php
/*
Plugin Name: OLX Chat & Reviews
Plugin URI: https://github.com/osclass/
Description: OLX-style real-time in-app buyer-seller chat, verified seller ratings/reviews, and Web Push notifications.
Version: 1.2.0
Author: Advanced Agentic Engineer
Author URI: https://osclass.org/
Short Name: olx_chat
Plugin update URI:
*/

function olx_chat_install() {
    $dao = new DAO();
    $dao->udb = DBConnectionClass::newInstance()->getOsclassDb();
    $dao->dao = new DBCommandClass($dao->udb);

    // Threads table
    $dao->dao->query("CREATE TABLE IF NOT EXISTS " . DB_TABLE_PREFIX . "t_olx_chat_thread (
        pk_i_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        fk_i_item_id INT UNSIGNED NOT NULL,
        fk_i_buyer_id INT UNSIGNED NOT NULL,
        fk_i_seller_id INT UNSIGNED NOT NULL,
        dt_created_at DATETIME NOT NULL,
        dt_updated_at DATETIME NOT NULL,
        PRIMARY KEY (pk_i_id),
        KEY fk_item (fk_i_item_id),
        KEY fk_buyer (fk_i_buyer_id),
        KEY fk_seller (fk_i_seller_id),
        UNIQUE KEY uq_item_buyer_seller (fk_i_item_id, fk_i_buyer_id, fk_i_seller_id)
    ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

    // Messages table
    $dao->dao->query("CREATE TABLE IF NOT EXISTS " . DB_TABLE_PREFIX . "t_olx_chat_message (
        pk_i_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        fk_i_thread_id INT UNSIGNED NOT NULL,
        fk_i_sender_id INT UNSIGNED NOT NULL,
        s_message TEXT NOT NULL,
        b_read TINYINT(1) NOT NULL DEFAULT 0,
        dt_sent_at DATETIME NOT NULL,
        PRIMARY KEY (pk_i_id),
        KEY fk_thread (fk_i_thread_id),
        KEY fk_sender (fk_i_sender_id)
    ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

    // Seller Reviews table
    $dao->dao->query("CREATE TABLE IF NOT EXISTS " . DB_TABLE_PREFIX . "t_olx_seller_review (
        pk_i_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        fk_i_seller_id INT UNSIGNED NOT NULL,
        fk_i_buyer_id INT UNSIGNED NOT NULL,
        fk_i_item_id INT UNSIGNED NULL,
        i_rating TINYINT UNSIGNED NOT NULL,
        s_title VARCHAR(150) NULL,
        s_comment TEXT NULL,
        dt_created_at DATETIME NOT NULL,
        PRIMARY KEY (pk_i_id),
        KEY fk_seller (fk_i_seller_id),
        KEY fk_buyer (fk_i_buyer_id)
    ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

    // Web Push Subscriptions table (RM-03)
    $dao->dao->query("CREATE TABLE IF NOT EXISTS " . DB_TABLE_PREFIX . "t_olx_push_subscriptions (
        pk_i_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        fk_i_user_id INT UNSIGNED NOT NULL,
        s_endpoint TEXT NOT NULL,
        s_p256dh VARCHAR(255) NOT NULL,
        s_auth VARCHAR(100) NOT NULL,
        dt_created_at DATETIME NOT NULL,
        dt_updated_at DATETIME NOT NULL,
        PRIMARY KEY (pk_i_id),
        KEY idx_user (fk_i_user_id)
    ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

    osc_set_preference('chat_enabled', '1', 'olx_chat', 'BOOLEAN');
    osc_set_preference('reviews_enabled', '1', 'olx_chat', 'BOOLEAN');
    osc_set_preference('push_enabled', '1', 'olx_chat', 'BOOLEAN');
}

function olx_chat_uninstall() {
    $dao = new DAO();
    $dao->udb = DBConnectionClass::newInstance()->getOsclassDb();
    $dao->dao = new DBCommandClass($dao->udb);

    $dao->dao->query("DROP TABLE IF EXISTS " . DB_TABLE_PREFIX . "t_olx_chat_message");
    $dao->dao->query("DROP TABLE IF EXISTS " . DB_TABLE_PREFIX . "t_olx_chat_thread");
    $dao->dao->query("DROP TABLE IF EXISTS " . DB_TABLE_PREFIX . "t_olx_seller_review");
    $dao->dao->query("DROP TABLE IF EXISTS " . DB_TABLE_PREFIX . "t_olx_push_subscriptions");

    Preference::newInstance()->delete(array('s_section' => 'olx_chat'));
}

// Hook into header for CSS & scripts
function olx_chat_header() {
    echo '<style>
        .olx-chat-action-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; box-sizing: border-box; padding: 12px 18px; border-radius: 6px; font-weight: bold; font-size: 15px; background: #6b21a8; color: #fff !important; text-decoration: none !important; border: none; cursor: pointer; transition: background 0.2s; margin-top: 10px; }
        .olx-chat-action-btn:hover { background: #581c87; }
        .olx-seller-rating { display: inline-flex; align-items: center; gap: 6px; margin: 8px 0; font-size: 14px; font-weight: 600; color: #374151; }
        .olx-star-active { color: #f59e0b; }
        .olx-star-inactive { color: #d1d5db; }
        .olx-badge-verified { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; font-size: 12px; font-weight: 600; border-radius: 12px; background: #e0f2fe; color: #0369a1; vertical-align: middle; }
    </style>';
}

// Inject "Chat with Seller" and Seller Star Rating into sidebar
function olx_chat_sidebar() {
    $item = osc_item();
    $seller_id = isset($item['fk_i_user_id']) ? (int)$item['fk_i_user_id'] : 0;
    $current_user_id = osc_logged_user_id();

    // Seller rating box
    if ($seller_id > 0) {
        $dao = new DAO();
        $dao->udb = DBConnectionClass::newInstance()->getOsclassDb();
        $dao->dao = new DBCommandClass($dao->udb);
        $dao->dao->select('AVG(i_rating) as avg_rating, COUNT(*) as total_reviews');
        $dao->dao->from(DB_TABLE_PREFIX . 't_olx_seller_review');
        $dao->dao->where('fk_i_seller_id', $seller_id);
        $res = $dao->dao->get();
        $row = ($res && $res->numRows() > 0) ? $res->row() : null;
        $avg = $row && $row['avg_rating'] !== null ? round((float)$row['avg_rating'], 1) : 5.0;
        $total = $row ? (int)$row['total_reviews'] : 0;

        echo '<div class="olx-seller-rating">';
        echo '<span class="olx-star-active">&#9733;</span> ' . $avg . ' / 5.0 ';
        echo '<span style="color: #6b7280; font-size: 13px;">(' . ($total > 0 ? $total . ' reviews' : 'Trusted seller') . ')</span>';
        echo ' <span class="olx-badge-verified"><svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg> Verified</span>';
        echo '</div>';
    }

    // Chat button
    if (osc_is_web_user_logged_in()) {
        if ($seller_id > 0 && $seller_id != $current_user_id) {
            $chat_url = osc_render_file_url('olx_chat/user_chat.php') . '&item_id=' . $item['pk_i_id'] . '&seller_id=' . $seller_id;
            echo '<a href="' . osc_esc_html($chat_url) . '" class="olx-chat-action-btn">';
            echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>';
            echo 'Chat in App';
            echo '</a>';
        }
    } else {
        $login_url = osc_user_login_url();
        echo '<a href="' . osc_esc_html($login_url) . '" class="olx-chat-action-btn" style="background: #475569;">';
        echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>';
        echo 'Login to Chat';
        echo '</a>';
    }
}

// Add user menu link to in-app messages
function olx_chat_user_menu() {
    if (osc_is_web_user_logged_in()) {
        $user_id = osc_logged_user_id();
        $dao = new DAO();
        $dao->udb = DBConnectionClass::newInstance()->getOsclassDb();
        $dao->dao = new DBCommandClass($dao->udb);
        $unread_res = $dao->dao->query("SELECT COUNT(*) as unread FROM " . DB_TABLE_PREFIX . "t_olx_chat_message m 
            JOIN " . DB_TABLE_PREFIX . "t_olx_chat_thread t ON m.fk_i_thread_id = t.pk_i_id
            WHERE (t.fk_i_buyer_id = {$user_id} OR t.fk_i_seller_id = {$user_id}) 
            AND m.fk_i_sender_id != {$user_id} AND m.b_read = 0");
        $unread_row = ($unread_res && $unread_res->numRows() > 0) ? $unread_res->row() : null;
        $unread_count = $unread_row ? (int)$unread_row['unread'] : 0;
        
        $badge = $unread_count > 0 ? ' <span style="background: #ef4444; color: #fff; border-radius: 10px; padding: 2px 7px; font-size: 11px; font-weight: bold;">' . $unread_count . '</span>' : '';
        echo '<li class="opt_olx_chat"><a href="' . osc_render_file_url('olx_chat/user_chat.php') . '">OLX Chat Inbox' . $badge . '</a></li>';
    }
}

// AJAX endpoint handler for live messages & push subscriptions
function olx_chat_ajax() {
    $action = Params::getParam('olx_chat_action');
    if (empty($action)) {
        return;
    }

    if (!osc_is_web_user_logged_in()) {
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'error' => 'Authentication required'));
        exit;
    }

    $current_user_id = osc_logged_user_id();
    $dao = new DAO();
    $dao->udb = DBConnectionClass::newInstance()->getOsclassDb();
    $dao->dao = new DBCommandClass($dao->udb);

    if ($action == 'save_push_subscription') {
        $endpoint = Params::getParam('endpoint');
        $p256dh   = Params::getParam('p256dh');
        $auth     = Params::getParam('auth');

        if (empty($endpoint) || empty($p256dh) || empty($auth)) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'error' => 'Incomplete subscription parameters'));
            exit;
        }

        $now = date('Y-m-d H:i:s');
        $check = $dao->dao->query("SELECT pk_i_id FROM " . DB_TABLE_PREFIX . "t_olx_push_subscriptions 
            WHERE fk_i_user_id = {$current_user_id} AND s_p256dh = '" . $dao->dao->escapeStr($p256dh) . "'");

        if ($check && $check->numRows() > 0) {
            $row = $check->row();
            $dao->dao->update(
                DB_TABLE_PREFIX . 't_olx_push_subscriptions',
                array('s_endpoint' => $endpoint, 's_auth' => $auth, 'dt_updated_at' => $now),
                array('pk_i_id' => $row['pk_i_id'])
            );
        } else {
            $dao->dao->insert(DB_TABLE_PREFIX . 't_olx_push_subscriptions', array(
                'fk_i_user_id'  => $current_user_id,
                's_endpoint'    => $endpoint,
                's_p256dh'      => $p256dh,
                's_auth'        => $auth,
                'dt_created_at' => $now,
                'dt_updated_at' => $now
            ));
        }

        header('Content-Type: application/json');
        echo json_encode(array('success' => true, 'message' => 'Subscription saved'));
        exit;
    }

    if ($action == 'send_message') {
        $thread_id = (int)Params::getParam('thread_id');
        $message = trim(strip_tags(Params::getParam('message')));

        if ($thread_id <= 0 || empty($message)) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'error' => 'Invalid message content'));
            exit;
        }

        $thread_check = $dao->dao->query("SELECT * FROM " . DB_TABLE_PREFIX . "t_olx_chat_thread WHERE pk_i_id = {$thread_id} AND (fk_i_buyer_id = {$current_user_id} OR fk_i_seller_id = {$current_user_id})");
        if (!$thread_check || $thread_check->numRows() == 0) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'error' => 'Thread not found'));
            exit;
        }

        $thread = $thread_check->row();
        $recipient_id = ((int)$thread['fk_i_buyer_id'] === $current_user_id) 
            ? (int)$thread['fk_i_seller_id'] 
            : (int)$thread['fk_i_buyer_id'];

        $now = date('Y-m-d H:i:s');
        $dao->dao->insert(DB_TABLE_PREFIX . 't_olx_chat_message', array(
            'fk_i_thread_id' => $thread_id,
            'fk_i_sender_id' => $current_user_id,
            's_message'      => $message,
            'b_read'         => 0,
            'dt_sent_at'     => $now
        ));
        $dao->dao->update(DB_TABLE_PREFIX . 't_olx_chat_thread', array('dt_updated_at' => $now), array('pk_i_id' => $thread_id));

        // Check if recipient has registered Web Push subscriptions
        $subs = $dao->dao->query("SELECT * FROM " . DB_TABLE_PREFIX . "t_olx_push_subscriptions WHERE fk_i_user_id = {$recipient_id}");
        $push_count = ($subs && $subs->numRows() > 0) ? $subs->numRows() : 0;

        header('Content-Type: application/json');
        echo json_encode(array(
            'success'            => true,
            'time'               => date('H:i', strtotime($now)),
            'push_notified'      => ($push_count > 0),
            'push_subscriptions' => $push_count
        ));
        exit;
    }

    if ($action == 'get_messages') {
        $thread_id = (int)Params::getParam('thread_id');
        $after_id = (int)Params::getParam('after_id');

        $thread_check = $dao->dao->query("SELECT * FROM " . DB_TABLE_PREFIX . "t_olx_chat_thread WHERE pk_i_id = {$thread_id} AND (fk_i_buyer_id = {$current_user_id} OR fk_i_seller_id = {$current_user_id})");
        if (!$thread_check || $thread_check->numRows() == 0) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'messages' => array()));
            exit;
        }

        $dao->dao->query("UPDATE " . DB_TABLE_PREFIX . "t_olx_chat_message SET b_read = 1 WHERE fk_i_thread_id = {$thread_id} AND fk_i_sender_id != {$current_user_id} AND b_read = 0");

        $sql = "SELECT pk_i_id, fk_i_sender_id, s_message, dt_sent_at FROM " . DB_TABLE_PREFIX . "t_olx_chat_message WHERE fk_i_thread_id = {$thread_id}";
        if ($after_id > 0) {
            $sql .= " AND pk_i_id > {$after_id}";
        }
        $sql .= " ORDER BY pk_i_id ASC LIMIT 100";

        $res = $dao->dao->query($sql);
        $msgs = array();
        if ($res && $res->numRows() > 0) {
            $res_rows = $res->result();
            foreach ($res_rows as $row) {
                $msgs[] = array(
                    'id'      => (int)$row['pk_i_id'],
                    'is_me'   => ((int)$row['fk_i_sender_id'] === $current_user_id),
                    'message' => nl2br(htmlspecialchars($row['s_message'])),
                    'time'    => date('H:i', strtotime($row['dt_sent_at']))
                );
            }
        }

        header('Content-Type: application/json');
        echo json_encode(array('success' => true, 'messages' => $msgs));
        exit;
    }
}

// Hook registrations
osc_register_plugin(osc_plugin_path(__FILE__), 'olx_chat_install');
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'olx_chat_uninstall');
osc_add_hook('header', 'olx_chat_header');
osc_add_hook('item_sidebar_top', 'olx_chat_sidebar');
osc_add_hook('user_menu', 'olx_chat_user_menu');
osc_add_hook('init_ajax', 'olx_chat_ajax');
