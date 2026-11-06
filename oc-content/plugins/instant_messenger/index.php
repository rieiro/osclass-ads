<?php
/*
Plugin Name: Instant Messenger
Plugin URI: https://osclasspoint.com/osclass-plugins/chat-messaging/instant-messenger-plugin-i50
Description: Complete live chat and instant messaging system for buyers and sellers with attachments, guest support, email alerts, and real-time AJAX stream.
Version: 1.5.0
Author: Advanced Agentic Engineer
Author URI: https://osclass.org/
Short Name: instant_messenger
Plugin update URI:
*/

define('IM_PATH', dirname(__FILE__) . '/');
define('IM_URL', osc_plugin_url(__FILE__));

require_once IM_PATH . 'classes/InstantMessenger.php';

// Install / Uninstall Callbacks
function im_install() {
    InstantMessenger::newInstance()->install();
}
function im_uninstall() {
    InstantMessenger::newInstance()->uninstall();
}

osc_register_plugin(osc_plugin_path(__FILE__), 'im_install');
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'im_uninstall');

// Register Friendly Routes
function im_routes() {
    osc_add_route('im-inbox', 'messages/?', 'messages', osc_plugin_folder(__FILE__) . 'inbox.php', false);
}
osc_add_hook('init', 'im_routes');

// Header Styles & Scripts
function im_header() {
    echo '<link rel="stylesheet" type="text/css" href="' . IM_URL . 'assets/css/messenger.css?v=' . filemtime(IM_PATH . 'assets/css/messenger.css') . '">' . PHP_EOL;
}
osc_add_hook('header', 'im_header');

function im_footer() {
    echo '<script src="' . IM_URL . 'assets/js/messenger.js?v=' . filemtime(IM_PATH . 'assets/js/messenger.js') . '"></script>' . PHP_EOL;
}
osc_add_hook('footer', 'im_footer');

// Top Header Navbar Messages Link (Prominent for logged-in users)
function im_header_nav_link() {
    if (osc_is_web_user_logged_in() && osc_get_preference('im_header_badge', 'instant_messenger')) {
        $unread = InstantMessenger::newInstance()->countUserUnread(osc_logged_user_id());
        $badge = ($unread > 0) ? ' <span class="im-header-badge">' . $unread . '</span>' : '';
        $inbox_url = osc_base_url(true) . '?page=custom&file=instant_messenger/inbox.php';
        echo '<a href="' . $inbox_url . '" class="im-header-nav-btn isDesktop isTablet" style="display:inline-flex; align-items:center; gap:6px; font-weight:600; font-size:13px; color:#0284c7; padding:6px 14px; margin-right:8px; border-radius:20px; background:#f0f9ff; border:1px solid #bae6fd; text-decoration:none;"><i class="fa fa-comments"></i> ' . __('Messages', 'instant_messenger') . $badge . '</a>';
    }
}
osc_add_hook('header_links', 'im_header_nav_link');

// Listing Page Chat Launcher Button
function im_item_chat_button() {
    if (osc_item_id() > 0) {
        require IM_PATH . 'item_chat.php';
    }
}
osc_add_hook('item_sidebar_top', 'im_item_chat_button', 8);

// User Menu Filter (Insert "Messages" into User Sidebar Menu)
function im_user_menu_filter($options) {
    if (osc_is_web_user_logged_in()) {
        $unread = InstantMessenger::newInstance()->countUserUnread(osc_logged_user_id());
        $badge = ($unread > 0) ? ' <span class="im-header-badge" style="float:right; margin-top:2px;">' . $unread . '</span>' : '';
        $inbox_url = osc_base_url(true) . '?page=custom&file=instant_messenger/inbox.php';
        
        $new_options = array();
        foreach ($options as $opt) {
            $new_options[] = $opt;
            if (isset($opt['class']) && $opt['class'] === 'opt_items') {
                $new_options[] = array(
                    'name'  => '<i class="fa fa-comments" style="color:#0284c7; margin-right:4px;"></i> ' . __('Messages', 'instant_messenger') . $badge,
                    'url'   => $inbox_url,
                    'class' => 'opt_im_messages' . (Params::getParam('file') === 'instant_messenger/inbox.php' ? ' active' : '')
                );
            }
        }
        return $new_options;
    }
    return $options;
}
osc_add_filter('user_menu_filter', 'im_user_menu_filter');

// Recent Conversations Widget directly inside User Sidebar
function im_sidebar_recent_threads() {
    if (osc_is_web_user_logged_in()) {
        $user_id = (int)osc_logged_user_id();
        $threads = InstantMessenger::newInstance()->getUserThreads($user_id, 6);
        if (!empty($threads)) {
            $inbox_base = osc_base_url(true) . '?page=custom&file=instant_messenger/inbox.php';
            echo '<div class="im-sidebar-threads-widget" style="margin-top: 20px; padding-top: 14px; border-top: 1px solid #e2e8f0;">';
            echo '<div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">';
            echo '<span><i class="fa fa-comments" style="color:#0284c7;"></i> ' . __('Recent Chats', 'instant_messenger') . '</span>';
            echo '<a href="' . $inbox_base . '" style="font-size: 11px; color: #0284c7; text-transform: none; text-decoration: underline; font-weight:600;">' . __('Open Inbox &rarr;', 'instant_messenger') . '</a>';
            echo '</div>';
            echo '<ul style="list-style: none; padding: 0; margin: 0;">';
            foreach ($threads as $th) {
                $is_sender = ((int)$th['sender_id'] === $user_id);
                $name = $is_sender ? (!empty($th['receiver_name']) ? $th['receiver_name'] : __('Seller', 'instant_messenger')) : (!empty($th['sender_name']) ? $th['sender_name'] : __('Buyer', 'instant_messenger'));
                $unread = $is_sender ? (int)$th['sender_unread'] : (int)$th['receiver_unread'];
                $badge = ($unread > 0) ? ' <span style="background:#ef4444; color:#fff; font-size:10px; font-weight:700; padding:1px 6px; border-radius:10px; float:right;">' . $unread . '</span>' : '';
                $chat_url = $inbox_base . '&thread_id=' . $th['id'];
                
                echo '<li style="margin-bottom: 8px;">';
                echo '<a href="' . $chat_url . '" style="display: block; padding: 8px 10px; border-radius: 6px; background: #f8fafc; border: 1px solid #e2e8f0; text-decoration: none; color: #1e293b; font-size: 12px; transition: background 0.15s; line-height: 1.3;">';
                echo '<div style="font-weight: 700; color: #0f172a; margin-bottom: 2px;">' . osc_esc_html($name) . $badge . '</div>';
                if (!empty($th['title'])) {
                    echo '<div style="font-size: 11px; color: #0284c7; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px;"><i class="fa fa-tag" style="font-size: 9px;"></i> ' . osc_esc_html($th['title']) . '</div>';
                }
                echo '<div style="font-size: 11px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">' . osc_esc_html($th['last_message']) . '</div>';
                echo '</a>';
                echo '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
    }
}
osc_add_hook('user_menu', 'im_sidebar_recent_threads');

// AJAX Router
function im_ajax_handler() {
    $action = Params::getParam('action');
    if (strpos($action, 'im_') === 0) {
        require_once IM_PATH . 'ajax.php';
        exit;
    }
}
osc_add_hook('init_ajax', 'im_ajax_handler');

// Admin Navigation Menu
function im_admin_menu() {
    osc_add_admin_menu_page(
        __('Instant Messenger', 'instant_messenger'),
        osc_admin_render_plugin_url('instant_messenger/admin/threads.php'),
        'im_admin_menu',
        'administrator',
        'dashicons-format-chat'
    );

    osc_add_admin_submenu_page(
        'im_admin_menu',
        __('Conversations', 'instant_messenger'),
        osc_admin_render_plugin_url('instant_messenger/admin/threads.php'),
        'im_admin_sub_threads',
        'administrator'
    );

    osc_add_admin_submenu_page(
        'im_admin_menu',
        __('Settings', 'instant_messenger'),
        osc_admin_render_plugin_url('instant_messenger/admin/settings.php'),
        'im_admin_sub_settings',
        'administrator'
    );
}
osc_add_hook('init_admin', 'im_admin_menu');
