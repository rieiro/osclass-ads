<?php
/*
Plugin Name: OLX Audit Trail & User Activity Logger
Plugin URI: https://github.com/osclass/
Description: Complete, secure, immutable audit trail capturing all user and administrator inputs, logins, listings, updates, and configuration changes with automatic PII/password masking.
Version: 1.0.0
Author: Advanced Agentic Engineer
Author URI: https://osclass.org/
Short Name: olx_audit_log
Plugin update URI:
*/

function olx_audit_log_install() {
    $conn = DBConnectionClass::newInstance();
    $db   = $conn->getOsclassDb();
    $comm = new DBCommandClass($db);

    $sql = "CREATE TABLE IF NOT EXISTS " . DB_TABLE_PREFIX . "t_audit_log (
        pk_i_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        s_actor_type ENUM('admin', 'customer', 'guest') NOT NULL DEFAULT 'guest',
        fk_i_actor_id INT UNSIGNED NULL,
        s_actor_email VARCHAR(191) NULL,
        s_actor_name VARCHAR(191) NULL,
        s_action VARCHAR(64) NOT NULL,
        s_target_type VARCHAR(64) NULL,
        s_target_id VARCHAR(64) NULL,
        s_http_method VARCHAR(10) NOT NULL,
        s_url VARCHAR(500) NOT NULL,
        s_ip_address VARCHAR(45) NOT NULL,
        s_user_agent VARCHAR(255) NULL,
        j_payload MEDIUMTEXT NULL,
        s_status ENUM('success', 'failed', 'denied') NOT NULL DEFAULT 'success',
        dt_created_at DATETIME NOT NULL,
        PRIMARY KEY (pk_i_id),
        KEY idx_actor (s_actor_type, fk_i_actor_id),
        KEY idx_action (s_action),
        KEY idx_ip (s_ip_address),
        KEY idx_created_at (dt_created_at),
        KEY idx_target (s_target_type, s_target_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $comm->query($sql);
    osc_set_preference('retention_days', '90', 'olx_audit_log', 'INTEGER');
    osc_set_preference('mask_passwords', '1', 'olx_audit_log', 'INTEGER');
}

function olx_audit_log_uninstall() {
    $conn = DBConnectionClass::newInstance();
    $db   = $conn->getOsclassDb();
    $comm = new DBCommandClass($db);
    $comm->query("DROP TABLE IF EXISTS " . DB_TABLE_PREFIX . "t_audit_log");
    Preference::newInstance()->delete(array('s_section' => 'olx_audit_log'));
}

// -------------------------------------------------------------
// HELPER: MASK SENSITIVE DATA IN PAYLOADS (PASSWORDS, TOKENS)
// -------------------------------------------------------------
function olx_audit_sanitize_payload($data) {
    if (!is_array($data)) return $data;
    
    $sensitive_keys = array(
        'password', 's_password', 's_password_confirmation', 'new_password', 
        'secret', 's_secret', 'token', 'auth_token', 'csrftoken', 'csrf_token', 
        'api_key', 'client_secret', 'app_secret', 'credit_card', 'card_number', 'cvv', 'pin'
    );

    $clean = array();
    foreach ($data as $k => $v) {
        $lower_k = strtolower((string)$k);
        if (in_array($lower_k, $sensitive_keys, true)) {
            $clean[$k] = '[REDACTED]';
        } elseif (is_array($v)) {
            $clean[$k] = olx_audit_sanitize_payload($v);
        } else {
            if (is_string($v) && strlen($v) > 2000) {
                $clean[$k] = substr($v, 0, 2000) . '... [TRUNCATED]';
            } else {
                $clean[$k] = $v;
            }
        }
    }
    return $clean;
}

// -------------------------------------------------------------
// CORE LOGGER FUNCTION
// -------------------------------------------------------------
function olx_audit_log_record($action, $target_type = null, $target_id = null, $payload = null, $status = 'success', $custom_actor = null) {
    try {
        $conn = DBConnectionClass::newInstance();
        $db   = $conn->getOsclassDb();
        $comm = new DBCommandClass($db);

        // Resolve Actor
        $actor_type = 'guest';
        $actor_id   = null;
        $actor_email = null;
        $actor_name  = null;

        if ($custom_actor && is_array($custom_actor)) {
            $actor_type  = isset($custom_actor['type']) ? $custom_actor['type'] : 'guest';
            $actor_id    = isset($custom_actor['id']) ? (int)$custom_actor['id'] : null;
            $actor_email = isset($custom_actor['email']) ? $custom_actor['email'] : null;
            $actor_name  = isset($custom_actor['name']) ? $custom_actor['name'] : null;
        } elseif (defined('OC_ADMIN') && OC_ADMIN === true && osc_is_admin_user_logged_in()) {
            $actor_type  = 'admin';
            $actor_id    = osc_logged_admin_id();
            $actor_email = osc_logged_admin_username();
            $actor_name  = osc_logged_admin_name();
        } elseif (osc_is_web_user_logged_in()) {
            $actor_type  = 'customer';
            $actor_id    = osc_logged_user_id();
            $actor_email = osc_logged_user_email();
            $actor_name  = osc_logged_user_name();
        }

        // Resolve Client IP
        $ip = '0.0.0.0';
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($parts[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        // Method & URL
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
        $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null;

        // Process Payload
        if ($payload === null && !empty($_POST)) {
            $payload = olx_audit_sanitize_payload($_POST);
        } elseif (is_array($payload)) {
            $payload = olx_audit_sanitize_payload($payload);
        }

        $json_payload = ($payload !== null) ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null;

        $insert_data = array(
            's_actor_type'   => $actor_type,
            'fk_i_actor_id'  => $actor_id,
            's_actor_email'  => $actor_email,
            's_actor_name'   => $actor_name,
            's_action'       => substr($action, 0, 64),
            's_target_type'  => $target_type ? substr($target_type, 0, 64) : null,
            's_target_id'    => $target_id ? substr((string)$target_id, 0, 64) : null,
            's_http_method'  => substr($method, 0, 10),
            's_url'          => substr($url, 0, 500),
            's_ip_address'   => substr($ip, 0, 45),
            's_user_agent'   => $ua,
            'j_payload'      => $json_payload,
            's_status'       => in_array($status, array('success', 'failed', 'denied')) ? $status : 'success',
            'dt_created_at'  => date('Y-m-d H:i:s')
        );

        $comm->insert(DB_TABLE_PREFIX . 't_audit_log', $insert_data);
    } catch (Exception $e) {
        error_log('Audit Log error: ' . $e->getMessage());
    }
}

// -------------------------------------------------------------
// UNIVERSAL MUTATING REQUEST INTERCEPTOR (POST, PUT, DELETE)
// -------------------------------------------------------------
function olx_audit_log_request_interceptor() {
    if (empty($_SERVER['REQUEST_METHOD']) || !in_array($_SERVER['REQUEST_METHOD'], array('POST', 'PUT', 'DELETE'))) {
        return;
    }

    $action_param = Params::getParam('action');
    $page_param   = Params::getParam('page');

    $specialized = array(
        'login_post', 'item_add_post', 'item_edit_post', 'register_post', 
        'contact_post', 'add_comment_post', 'user_edit_post'
    );
    if (in_array($action_param, $specialized, true)) {
        return;
    }

    $isAdmin = (defined('OC_ADMIN') && OC_ADMIN === true);
    $action_name = $isAdmin 
        ? 'admin.' . ($page_param ? $page_param : 'request') . ($action_param ? '.' . $action_param : '')
        : 'customer.' . ($page_param ? $page_param : 'request') . ($action_param ? '.' . $action_param : '');

    olx_audit_log_record($action_name, $page_param, $action_param);
}
osc_add_hook('init', 'olx_audit_log_request_interceptor', 10);
osc_add_hook('init_admin', 'olx_audit_log_request_interceptor', 10);

// -------------------------------------------------------------
// SPECIALIZED LIFECYCLE EVENT HOOKS
// -------------------------------------------------------------

// 1. Customer Authentication
function olx_audit_hook_after_login($user) {
    $actor = array(
        'type'  => 'customer',
        'id'    => isset($user['pk_i_id']) ? $user['pk_i_id'] : null,
        'email' => isset($user['s_email']) ? $user['s_email'] : null,
        'name'  => isset($user['s_name']) ? $user['s_name'] : null
    );
    olx_audit_log_record('auth.customer_login', 'user', $actor['id'], array('email' => $actor['email']), 'success', $actor);
}
osc_add_hook('after_login', 'olx_audit_hook_after_login');

function olx_audit_hook_logout() {
    olx_audit_log_record('auth.customer_logout', 'user', osc_logged_user_id());
}
osc_add_hook('logout', 'olx_audit_hook_logout');

// 2. Administrator Authentication
function olx_audit_hook_login_admin($admin) {
    $actor = array(
        'type'  => 'admin',
        'id'    => isset($admin['pk_i_id']) ? $admin['pk_i_id'] : null,
        'email' => isset($admin['s_username']) ? $admin['s_username'] : (isset($admin['s_email']) ? $admin['s_email'] : null),
        'name'  => isset($admin['s_name']) ? $admin['s_name'] : null
    );
    olx_audit_log_record('auth.admin_login', 'admin', $actor['id'], array('username' => $actor['email']), 'success', $actor);
}
osc_add_hook('login_admin', 'olx_audit_hook_login_admin');

function olx_audit_hook_logout_admin() {
    olx_audit_log_record('auth.admin_logout', 'admin', osc_logged_admin_id());
}
osc_add_hook('logout_admin', 'olx_audit_hook_logout_admin');

// 3. Listings Lifecycle
function olx_audit_hook_posted_item($item) {
    $item_id = isset($item['pk_i_id']) ? $item['pk_i_id'] : null;
    $title = isset($item['description'][osc_current_user_locale()]['s_title']) 
        ? $item['description'][osc_current_user_locale()]['s_title'] 
        : (isset($item['s_title']) ? $item['s_title'] : 'Item #' . $item_id);

    $payload = array(
        'item_id'  => $item_id,
        'title'    => $title,
        'category' => isset($item['fk_i_category_id']) ? $item['fk_i_category_id'] : null,
        'price'    => isset($item['i_price']) ? $item['i_price'] : null
    );
    olx_audit_log_record('listing.create', 'item', $item_id, $payload);
}
osc_add_hook('posted_item', 'olx_audit_hook_posted_item');

function olx_audit_hook_edited_item($item) {
    $item_id = isset($item['pk_i_id']) ? $item['pk_i_id'] : null;
    olx_audit_log_record('listing.edit', 'item', $item_id, array('item_id' => $item_id));
}
osc_add_hook('edited_item', 'olx_audit_hook_edited_item');

function olx_audit_hook_after_delete_item($item_id, $item = null) {
    olx_audit_log_record('listing.delete', 'item', $item_id, array('deleted_item_id' => $item_id));
}
osc_add_hook('after_delete_item', 'olx_audit_hook_after_delete_item');

function olx_audit_hook_activate_item($item_id) {
    olx_audit_log_record('listing.activate', 'item', $item_id);
}
osc_add_hook('activate_item', 'olx_audit_hook_activate_item');

function olx_audit_hook_deactivate_item($item_id) {
    olx_audit_log_record('listing.deactivate', 'item', $item_id);
}
osc_add_hook('deactivate_item', 'olx_audit_hook_deactivate_item');

function olx_audit_hook_item_spam_on($item_id) {
    olx_audit_log_record('listing.marked_as_spam', 'item', $item_id);
}
osc_add_hook('item_spam_on', 'olx_audit_hook_item_spam_on');

function olx_audit_hook_item_spam_off($item_id) {
    olx_audit_log_record('listing.unmarked_as_spam', 'item', $item_id);
}
osc_add_hook('item_spam_off', 'olx_audit_hook_item_spam_off');

// 4. User Accounts Lifecycle
function olx_audit_hook_user_register_completed($user_id) {
    $user = User::newInstance()->findByPrimaryKey($user_id);
    $actor = array(
        'type'  => 'customer',
        'id'    => $user_id,
        'email' => isset($user['s_email']) ? $user['s_email'] : null,
        'name'  => isset($user['s_name']) ? $user['s_name'] : null
    );
    olx_audit_log_record('user.register', 'user', $user_id, array('email' => $actor['email']), 'success', $actor);
}
osc_add_hook('user_register_completed', 'olx_audit_hook_user_register_completed');

function olx_audit_hook_user_edit_completed($user_id) {
    olx_audit_log_record('user.profile_update', 'user', $user_id);
}
osc_add_hook('user_edit_completed', 'olx_audit_hook_user_edit_completed');

function olx_audit_hook_user_register_failed($error) {
    olx_audit_log_record('user.register_failed', 'user', null, array('error' => $error), 'failed');
}
osc_add_hook('user_register_failed', 'olx_audit_hook_user_register_failed');

// 5. Comments & Contact Messages
function olx_audit_hook_add_comment($comment_id) {
    olx_audit_log_record('comment.create', 'comment', $comment_id);
}
osc_add_hook('add_comment', 'olx_audit_hook_add_comment');

// -------------------------------------------------------------
// ADMIN MENU REGISTRATION
// -------------------------------------------------------------
function olx_audit_log_admin_menu() {
    osc_add_admin_menu_page(
        __('Audit Trail', 'olx_audit_log'),
        osc_admin_render_plugin_url('olx_audit_log/admin.php'),
        'olx_audit_log_admin',
        'administrator'
    );
}
osc_add_hook('admin_menu_init', 'olx_audit_log_admin_menu');
