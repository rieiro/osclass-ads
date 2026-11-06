<?php
if (!defined('ABS_PATH')) exit('ABS_PATH is not loaded. Direct access is not allowed.');

class InstantMessenger extends DAO {
    private static $instance;

    public static function newInstance() {
        if (!self::$instance instanceof self) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function __construct() {
        parent::__construct();
    }

    public function install() {
        $this->dao->query("CREATE TABLE IF NOT EXISTS " . DB_TABLE_PREFIX . "t_im_threads (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            item_id INT UNSIGNED NOT NULL DEFAULT 0,
            sender_id INT UNSIGNED NOT NULL DEFAULT 0,
            sender_name VARCHAR(150) NOT NULL DEFAULT '',
            sender_email VARCHAR(150) NOT NULL DEFAULT '',
            receiver_id INT UNSIGNED NOT NULL DEFAULT 0,
            receiver_name VARCHAR(150) NOT NULL DEFAULT '',
            receiver_email VARCHAR(150) NOT NULL DEFAULT '',
            secret_token VARCHAR(64) NOT NULL,
            title VARCHAR(255) NOT NULL DEFAULT '',
            status TINYINT NOT NULL DEFAULT 1,
            sender_unread INT UNSIGNED NOT NULL DEFAULT 0,
            receiver_unread INT UNSIGNED NOT NULL DEFAULT 0,
            last_message TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_sender (sender_id),
            KEY idx_receiver (receiver_id),
            KEY idx_item (item_id),
            KEY idx_token (secret_token),
            KEY idx_updated (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        $this->dao->query("CREATE TABLE IF NOT EXISTS " . DB_TABLE_PREFIX . "t_im_messages (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            thread_id INT UNSIGNED NOT NULL,
            sender_id INT UNSIGNED NOT NULL DEFAULT 0,
            sender_name VARCHAR(150) NOT NULL DEFAULT '',
            sender_email VARCHAR(150) NOT NULL DEFAULT '',
            message TEXT NOT NULL,
            attachment VARCHAR(255) NULL,
            attachment_original VARCHAR(255) NULL,
            attachment_mime VARCHAR(100) NULL,
            attachment_size INT UNSIGNED NOT NULL DEFAULT 0,
            is_read TINYINT NOT NULL DEFAULT 0,
            read_at DATETIME NULL,
            ip_address VARCHAR(45) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_thread (thread_id),
            KEY idx_sender (sender_id),
            KEY idx_read (is_read),
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        $this->dao->query("CREATE TABLE IF NOT EXISTS " . DB_TABLE_PREFIX . "t_im_blocks (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            blocked_user_id INT UNSIGNED NOT NULL DEFAULT 0,
            blocked_email VARCHAR(150) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_block (user_id, blocked_user_id, blocked_email)
        ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        osc_set_preference('im_guest_messaging', '1', 'instant_messenger', 'BOOLEAN');
        osc_set_preference('im_attachments_enabled', '1', 'instant_messenger', 'BOOLEAN');
        osc_set_preference('im_allowed_ext', 'jpg,jpeg,png,gif,webp,pdf,doc,docx,zip', 'instant_messenger', 'STRING');
        osc_set_preference('im_max_file_size', '5', 'instant_messenger', 'INTEGER');
        osc_set_preference('im_poll_interval', '4', 'instant_messenger', 'INTEGER');
        osc_set_preference('im_replace_contact_form', '1', 'instant_messenger', 'BOOLEAN');
        osc_set_preference('im_email_notifications', '1', 'instant_messenger', 'BOOLEAN');
        osc_set_preference('im_header_badge', '1', 'instant_messenger', 'BOOLEAN');
    }

    public function uninstall() {
        // Table preservation by default or optional drop
        // Preference::newInstance()->delete(array('s_section' => 'instant_messenger'));
    }

    public function getThread($thread_id) {
        $res = $this->dao->query("SELECT * FROM " . DB_TABLE_PREFIX . "t_im_threads WHERE id = " . (int)$thread_id);
        if ($res && $res->numRows() > 0) {
            return $res->row();
        }
        return false;
    }

    public function getThreadByToken($token) {
        $esc = $this->dao->escape($token);
        $res = $this->dao->query("SELECT * FROM " . DB_TABLE_PREFIX . "t_im_threads WHERE secret_token = {$esc}");
        if ($res && $res->numRows() > 0) {
            return $res->row();
        }
        return false;
    }

    public function getOrCreateThread($item_id, $sender_id, $sender_name, $sender_email, $receiver_id, $receiver_name, $receiver_email, $title = '') {
        $item_id = (int)$item_id;
        $sender_id = (int)$sender_id;
        $receiver_id = (int)$receiver_id;

        // If registered user, look for existing thread for same item and participants
        if ($sender_id > 0 && $receiver_id > 0) {
            $sql = "SELECT id FROM " . DB_TABLE_PREFIX . "t_im_threads 
                    WHERE item_id = {$item_id} 
                    AND ((sender_id = {$sender_id} AND receiver_id = {$receiver_id}) OR (sender_id = {$receiver_id} AND receiver_id = {$sender_id}))";
            $res = $this->dao->query($sql);
            if ($res && $res->numRows() > 0) {
                $row = $res->row();
                return (int)$row['id'];
            }
        } elseif ($sender_id == 0 && !empty($sender_email) && $receiver_id > 0) {
            // Guest inquiry: look for matching email + item + seller
            $esc_email = $this->dao->escape($sender_email);
            $sql = "SELECT id FROM " . DB_TABLE_PREFIX . "t_im_threads 
                    WHERE item_id = {$item_id} 
                    AND sender_email = {$esc_email} 
                    AND receiver_id = {$receiver_id}";
            $res = $this->dao->query($sql);
            if ($res && $res->numRows() > 0) {
                $row = $res->row();
                return (int)$row['id'];
            }
        }

        // Generate unique 32-character secret token
        $secret_token = md5(uniqid(mt_rand(), true));
        $now = date('Y-m-d H:i:s');

        $this->dao->insert(DB_TABLE_PREFIX . 't_im_threads', array(
            'item_id'         => $item_id,
            'sender_id'       => $sender_id,
            'sender_name'     => $sender_name,
            'sender_email'    => $sender_email,
            'receiver_id'     => $receiver_id,
            'receiver_name'   => $receiver_name,
            'receiver_email'  => $receiver_email,
            'secret_token'    => $secret_token,
            'title'           => $title,
            'status'          => 1,
            'sender_unread'   => 0,
            'receiver_unread' => 0,
            'last_message'    => '',
            'created_at'      => $now,
            'updated_at'      => $now
        ));

        return (int)$this->dao->insertedId();
    }

    public function getUserThreads($user_id, $limit = 50) {
        $user_id = (int)$user_id;
        $sql = "SELECT t.*, 
                i.dt_pub_date, i.i_price, i.fk_c_currency_code,
                d.s_title as item_title
                FROM " . DB_TABLE_PREFIX . "t_im_threads t
                LEFT JOIN " . DB_TABLE_PREFIX . "t_item i ON t.item_id = i.pk_i_id
                LEFT JOIN " . DB_TABLE_PREFIX . "t_item_description d ON (t.item_id = d.fk_i_item_id AND d.fk_c_locale_code = '" . osc_current_user_locale() . "')
                WHERE (t.sender_id = {$user_id} OR t.receiver_id = {$user_id})
                ORDER BY t.updated_at DESC
                LIMIT " . (int)$limit;
        
        $res = $this->dao->query($sql);
        if ($res && $res->numRows() > 0) {
            return $res->result();
        }
        return array();
    }

    public function countUserUnread($user_id) {
        $user_id = (int)$user_id;
        if ($user_id <= 0) return 0;

        $sql = "SELECT 
                SUM(CASE WHEN sender_id = {$user_id} THEN sender_unread ELSE receiver_unread END) as total_unread
                FROM " . DB_TABLE_PREFIX . "t_im_threads
                WHERE sender_id = {$user_id} OR receiver_id = {$user_id}";
        
        $res = $this->dao->query($sql);
        if ($res && $res->numRows() > 0) {
            $row = $res->row();
            return (int)$row['total_unread'];
        }
        return 0;
    }

    public function getMessages($thread_id, $after_id = 0, $limit = 100) {
        $thread_id = (int)$thread_id;
        $after_id = (int)$after_id;
        
        $sql = "SELECT * FROM " . DB_TABLE_PREFIX . "t_im_messages WHERE thread_id = {$thread_id}";
        if ($after_id > 0) {
            $sql .= " AND id > {$after_id}";
        }
        $sql .= " ORDER BY id ASC LIMIT " . (int)$limit;

        $res = $this->dao->query($sql);
        if ($res && $res->numRows() > 0) {
            return $res->result();
        }
        return array();
    }

    public function sendMessage($thread_id, $sender_id, $sender_name, $sender_email, $message, $file = null) {
        $thread_id = (int)$thread_id;
        $thread = $this->getThread($thread_id);
        if (!$thread) return false;

        $now = date('Y-m-d H:i:s');
        $ip = osc_get_ip();

        // Attachment processing
        $stored_filename = null;
        $original_name = null;
        $mime_type = null;
        $file_size = 0;

        if ($file && isset($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
            $upload_dir = osc_content_path() . 'uploads/instant_messenger/';
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_exts = explode(',', strtolower((string)osc_get_preference('im_allowed_ext', 'instant_messenger')));
            $allowed_exts = array_map('trim', $allowed_exts);

            $max_mb = (int)osc_get_preference('im_max_file_size', 'instant_messenger');
            if ($max_mb <= 0) $max_mb = 5;

            if (in_array($ext, $allowed_exts) && $file['size'] <= ($max_mb * 1024 * 1024)) {
                $stored_filename = 'im_' . $thread_id . '_' . time() . '_' . substr(md5(uniqid(mt_rand(), true)), 0, 8) . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $upload_dir . $stored_filename)) {
                    $original_name = $file['name'];
                    $mime_type = $file['type'];
                    $file_size = (int)$file['size'];
                } else {
                    $stored_filename = null;
                }
            }
        }

        // Double-submission guard (same thread, sender, and message within last 3 seconds)
        if (!empty($message)) {
            $esc_msg = $this->dao->escape($message);
            $check_sql = sprintf(
                "SELECT id FROM %st_im_messages WHERE thread_id = %d AND sender_id = %d AND message = %s AND created_at >= '%s' ORDER BY id DESC LIMIT 1",
                DB_TABLE_PREFIX,
                $thread_id,
                (int)$sender_id,
                $esc_msg,
                date('Y-m-d H:i:s', time() - 3)
            );
            $dup = $this->dao->query($check_sql);
            if ($dup && $dup->numRows() > 0) {
                $dup_row = $dup->row();
                return (int)$dup_row['id'];
            }
        }

        // Insert message
        $this->dao->insert(DB_TABLE_PREFIX . 't_im_messages', array(
            'thread_id'           => $thread_id,
            'sender_id'           => (int)$sender_id,
            'sender_name'         => $sender_name,
            'sender_email'        => $sender_email,
            'message'             => $message,
            'attachment'          => $stored_filename,
            'attachment_original' => $original_name,
            'attachment_mime'     => $mime_type,
            'attachment_size'     => $file_size,
            'is_read'             => 0,
            'read_at'             => null,
            'ip_address'          => $ip,
            'created_at'          => $now
        ));

        $msg_id = (int)$this->dao->insertedId();

        // Update thread status & unread counters
        // If current sender is thread sender, increment receiver_unread, else increment sender_unread
        $is_sender = ((int)$thread['sender_id'] > 0 && (int)$thread['sender_id'] === (int)$sender_id) 
            || ((int)$thread['sender_id'] === 0 && strtolower($thread['sender_email']) === strtolower($sender_email));

        $preview = !empty($message) ? $message : ($stored_filename ? '[' . __('Attachment', 'instant_messenger') . ']' : '');
        if (mb_strlen($preview) > 120) {
            $preview = mb_substr($preview, 0, 117) . '...';
        }

        if ($is_sender) {
            $this->dao->query(sprintf(
                "UPDATE %st_im_threads SET updated_at = '%s', last_message = %s, receiver_unread = receiver_unread + 1 WHERE id = %d",
                DB_TABLE_PREFIX,
                $now,
                $this->dao->escape($preview),
                $thread_id
            ));
        } else {
            $this->dao->query(sprintf(
                "UPDATE %st_im_threads SET updated_at = '%s', last_message = %s, sender_unread = sender_unread + 1 WHERE id = %d",
                DB_TABLE_PREFIX,
                $now,
                $this->dao->escape($preview),
                $thread_id
            ));
        }

        return $msg_id;
    }

    public function markThreadRead($thread_id, $reader_user_id = null, $reader_email = null) {
        $thread_id = (int)$thread_id;
        $thread = $this->getThread($thread_id);
        if (!$thread) return;

        $now = date('Y-m-d H:i:s');
        $is_sender = false;

        if ($reader_user_id !== null && (int)$reader_user_id > 0) {
            if ((int)$thread['sender_id'] === (int)$reader_user_id) {
                $is_sender = true;
            }
        } elseif (!empty($reader_email)) {
            if (strtolower($thread['sender_email']) === strtolower($reader_email)) {
                $is_sender = true;
            }
        }

        if ($is_sender) {
            // Reader is thread initiator: reset sender_unread
            $this->dao->update(DB_TABLE_PREFIX . 't_im_threads', array('sender_unread' => 0), array('id' => $thread_id));
            // Mark incoming messages as read
            $this->dao->query(sprintf(
                "UPDATE %st_im_messages SET is_read = 1, read_at = '%s' WHERE thread_id = %d AND is_read = 0 AND (sender_id != %d OR (sender_id = 0 AND sender_email != %s))",
                DB_TABLE_PREFIX,
                $now,
                $thread_id,
                (int)$reader_user_id,
                $this->dao->escape((string)$reader_email)
            ));
        } else {
            // Reader is recipient: reset receiver_unread
            $this->dao->update(DB_TABLE_PREFIX . 't_im_threads', array('receiver_unread' => 0), array('id' => $thread_id));
            // Mark incoming messages as read
            $this->dao->query(sprintf(
                "UPDATE %st_im_messages SET is_read = 1, read_at = '%s' WHERE thread_id = %d AND is_read = 0 AND (sender_id != %d OR (sender_id = 0 AND sender_email != %s))",
                DB_TABLE_PREFIX,
                $now,
                $thread_id,
                (int)$reader_user_id,
                $this->dao->escape((string)$reader_email)
            ));
        }
    }

    public function deleteThread($thread_id, $user_id = null) {
        $thread_id = (int)$thread_id;
        $thread = $this->getThread($thread_id);
        if (!$thread) return false;

        if ($user_id !== null && (int)$user_id > 0) {
            if ((int)$thread['sender_id'] !== (int)$user_id && (int)$thread['receiver_id'] !== (int)$user_id) {
                return false;
            }
        }

        // Delete attachments on disk
        $msgs = $this->dao->query("SELECT attachment FROM " . DB_TABLE_PREFIX . "t_im_messages WHERE thread_id = {$thread_id} AND attachment IS NOT NULL");
        if ($msgs && $msgs->numRows() > 0) {
            $upload_dir = osc_content_path() . 'uploads/instant_messenger/';
            $rows = $msgs->result();
            foreach ($rows as $m) {
                if (!empty($m['attachment']) && file_exists($upload_dir . $m['attachment'])) {
                    @unlink($upload_dir . $m['attachment']);
                }
            }
        }

        $this->dao->delete(DB_TABLE_PREFIX . 't_im_messages', array('thread_id' => $thread_id));
        $this->dao->delete(DB_TABLE_PREFIX . 't_im_threads', array('id' => $thread_id));
        return true;
    }

    public function isBlocked($user_id_1, $user_id_2) {
        $u1 = (int)$user_id_1;
        $u2 = (int)$user_id_2;
        if ($u1 <= 0 || $u2 <= 0) return false;

        $res = $this->dao->query("SELECT id FROM " . DB_TABLE_PREFIX . "t_im_blocks WHERE (user_id = {$u1} AND blocked_user_id = {$u2}) OR (user_id = {$u2} AND blocked_user_id = {$u1})");
        return ($res && $res->numRows() > 0);
    }

    public function blockUser($user_id, $blocked_user_id) {
        $u1 = (int)$user_id;
        $u2 = (int)$blocked_user_id;
        if ($u1 <= 0 || $u2 <= 0 || $u1 === $u2) return false;

        return $this->dao->insert(DB_TABLE_PREFIX . 't_im_blocks', array(
            'user_id'         => $u1,
            'blocked_user_id' => $u2,
            'created_at'      => date('Y-m-d H:i:s')
        ));
    }

    public function unblockUser($user_id, $blocked_user_id) {
        $u1 = (int)$user_id;
        $u2 = (int)$blocked_user_id;
        return $this->dao->delete(DB_TABLE_PREFIX . 't_im_blocks', array('user_id' => $u1, 'blocked_user_id' => $u2));
    }
}
