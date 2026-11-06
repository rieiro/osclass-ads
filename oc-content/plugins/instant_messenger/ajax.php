<?php
if (!defined('ABS_PATH')) exit('ABS_PATH is not loaded. Direct access is not allowed.');

$im = InstantMessenger::newInstance();
$action = Params::getParam('action');

// Helper to determine current participant
$current_user_id = osc_is_web_user_logged_in() ? (int)osc_logged_user_id() : 0;
$guest_token = trim((string)Params::getParam('token'));

// -------------------------------------------------------------
// GET MESSAGES (AJAX POLLING)
// -------------------------------------------------------------
if ($action === 'im_get_messages') {
    $thread_id = (int)Params::getParam('thread_id');
    $after_id  = (int)Params::getParam('after_id');

    $thread = $im->getThread($thread_id);
    if (!$thread) {
        echo json_encode(array('status' => 'error', 'error' => 'Thread not found'));
        exit;
    }

    // Verify Access: registered user or matching guest token
    $is_authorized = false;
    $is_sender = false;

    if ($current_user_id > 0) {
        if ((int)$thread['sender_id'] === $current_user_id) {
            $is_authorized = true;
            $is_sender = true;
        } elseif ((int)$thread['receiver_id'] === $current_user_id) {
            $is_authorized = true;
            $is_sender = false;
        }
    }

    if (!$is_authorized && !empty($guest_token) && $guest_token === $thread['secret_token']) {
        $is_authorized = true;
        $is_sender = true;
    }

    if (!$is_authorized) {
        echo json_encode(array('status' => 'error', 'error' => 'Unauthorized access'));
        exit;
    }

    // Mark as read
    if ($is_sender) {
        $im->markThreadRead($thread_id, $current_user_id, $thread['sender_email']);
    } else {
        $im->markThreadRead($thread_id, $current_user_id, $thread['receiver_email']);
    }

    // Fetch messages
    $raw_msgs = $im->getMessages($thread_id, $after_id);
    $out_msgs = array();
    $base_upload_url = osc_base_url() . 'oc-content/uploads/instant_messenger/';

    foreach ($raw_msgs as $m) {
        $is_me = false;
        if ($current_user_id > 0) {
            $is_me = ((int)$m['sender_id'] === $current_user_id);
        } else {
            $is_me = (empty($m['sender_id']) && strtolower($m['sender_email']) === strtolower($thread['sender_email']));
        }

        $att_url = null;
        $is_img = false;
        if (!empty($m['attachment'])) {
            $att_url = $base_upload_url . $m['attachment'];
            $ext = strtolower(pathinfo($m['attachment'], PATHINFO_EXTENSION));
            if (in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'))) {
                $is_img = true;
            }
        }

        $out_msgs[] = array(
            'id'              => (int)$m['id'],
            'is_me'           => $is_me,
            'sender_name'     => osc_esc_html($m['sender_name']),
            'message'         => nl2br(htmlspecialchars($m['message'])),
            'attachment_url'  => $att_url,
            'attachment_name' => osc_esc_html($m['attachment_original']),
            'is_image'        => $is_img,
            'is_read'         => (int)$m['is_read'],
            'time'            => date('H:i', strtotime($m['created_at']))
        );
    }

    echo json_encode(array('status' => 'ok', 'messages' => $out_msgs));
    exit;
}

// -------------------------------------------------------------
// SEND MESSAGE (AJAX)
// -------------------------------------------------------------
if ($action === 'im_send_message') {
    $thread_id = (int)Params::getParam('thread_id');
    $message   = trim(strip_tags((string)Params::getParam('message')));
    $file      = isset($_FILES['attachment']) ? $_FILES['attachment'] : null;

    $thread = $im->getThread($thread_id);
    if (!$thread) {
        echo json_encode(array('status' => 'error', 'error' => 'Thread not found'));
        exit;
    }

    // Verify Access & determine sender identity
    $sender_id = 0;
    $sender_name = '';
    $sender_email = '';
    $recipient_email = '';
    $recipient_name = '';

    if ($current_user_id > 0) {
        $user = User::newInstance()->findByPrimaryKey($current_user_id);
        $sender_id = $current_user_id;
        $sender_name = $user ? $user['s_name'] : osc_logged_user_name();
        $sender_email = $user ? $user['s_email'] : osc_logged_user_email();

        if ((int)$thread['sender_id'] === $current_user_id) {
            $recipient_email = $thread['receiver_email'];
            $recipient_name = $thread['receiver_name'];
        } elseif ((int)$thread['receiver_id'] === $current_user_id) {
            $recipient_email = $thread['sender_email'];
            $recipient_name = $thread['sender_name'];
        } else {
            echo json_encode(array('status' => 'error', 'error' => 'Unauthorized sender'));
            exit;
        }
    } elseif (!empty($guest_token) && $guest_token === $thread['secret_token']) {
        // Authorized Guest
        $sender_id = 0;
        $sender_name = $thread['sender_name'];
        $sender_email = $thread['sender_email'];
        $recipient_email = $thread['receiver_email'];
        $recipient_name = $thread['receiver_name'];
    } else {
        echo json_encode(array('status' => 'error', 'error' => 'Access denied. Please log in.'));
        exit;
    }

    if (empty($message) && (!$file || !is_uploaded_file($file['tmp_name']))) {
        echo json_encode(array('status' => 'error', 'error' => 'Please type a message or select a file.'));
        exit;
    }

    $msg_id = $im->sendMessage($thread_id, $sender_id, $sender_name, $sender_email, $message, $file);

    if ($msg_id) {
        // Send email alert if enabled
        if (osc_get_preference('im_email_notifications', 'instant_messenger') && !empty($recipient_email)) {
            $item_title = $thread['title'];
            $subject = sprintf(__('[%s] New message from %s regarding "%s"', 'instant_messenger'), osc_page_title(), $sender_name, $item_title);
            
            // Conversation direct link
            $chat_link = '';
            if ($current_user_id > 0 && (int)$thread['receiver_id'] === $current_user_id) {
                // Sender was seller, recipient is buyer (might be guest or registered)
                if ((int)$thread['sender_id'] > 0) {
                    $chat_link = osc_route_url('im-inbox', array('thread_id' => $thread_id));
                } else {
                    $chat_link = osc_base_url() . '?page=custom&file=instant_messenger/guest_chat.php&t=' . $thread['secret_token'];
                }
            } else {
                // Sender was buyer, recipient is seller (registered)
                $chat_link = osc_route_url('im-inbox', array('thread_id' => $thread_id));
            }

            $body = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">';
            $body .= '<h2 style="color: #0284c7; margin-top: 0;">' . sprintf(__('New message from %s', 'instant_messenger'), osc_esc_html($sender_name)) . '</h2>';
            $body .= '<p style="color: #64748b; font-size: 14px;">' . sprintf(__('Regarding: <strong>%s</strong>', 'instant_messenger'), osc_esc_html($item_title)) . '</p>';
            $body .= '<div style="background: #f1f5f9; padding: 15px; border-radius: 6px; font-size: 15px; color: #1e293b; margin: 20px 0;">' . nl2br(htmlspecialchars($message)) . '</div>';
            $body .= '<p><a href="' . $chat_link . '" style="display: inline-block; background: #0284c7; color: #ffffff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold;">' . __('Reply in Chat &rarr;', 'instant_messenger') . '</a></p>';
            $body .= '<hr style="border: none; border-top: 1px solid #e2e8f0; margin-top: 30px;">';
            $body .= '<p style="font-size: 12px; color: #94a3b8;">' . sprintf(__('This notification was sent by %s Instant Messenger.', 'instant_messenger'), osc_page_title()) . '</p>';
            $body .= '</div>';

            $params = array(
                'from'     => osc_contact_email(),
                'to'       => $recipient_email,
                'to_name'  => $recipient_name,
                'subject'  => $subject,
                'body'     => $body,
                'alt_body' => strip_tags($message)
            );
            @osc_sendMail($params);
        }

        echo json_encode(array('status' => 'ok', 'msg_id' => $msg_id));
    } else {
        echo json_encode(array('status' => 'error', 'error' => 'Failed to save message.'));
    }
    exit;
}

// -------------------------------------------------------------
// START CHAT (FROM LISTING PAGE)
// -------------------------------------------------------------
if ($action === 'im_start_chat') {
    $item_id = (int)Params::getParam('item_id');
    $message = trim(strip_tags((string)Params::getParam('message')));
    $file    = isset($_FILES['attachment']) ? $_FILES['attachment'] : null;

    $item = Item::newInstance()->findByPrimaryKey($item_id);
    if (!$item) {
        echo json_encode(array('status' => 'error', 'error' => 'Listing not found'));
        exit;
    }

    $seller_id = (int)$item['fk_i_user_id'];
    $seller_email = $item['s_contact_email'];
    $seller_name = $item['s_contact_name'];

    // Prevent seller chatting with themselves
    if ($current_user_id > 0 && $current_user_id === $seller_id) {
        echo json_encode(array('status' => 'error', 'error' => __("You cannot start a chat on your own listing.", 'instant_messenger')));
        exit;
    }

    // Determine sender credentials
    if ($current_user_id > 0) {
        $user = User::newInstance()->findByPrimaryKey($current_user_id);
        $sender_id = $current_user_id;
        $sender_name = $user ? $user['s_name'] : osc_logged_user_name();
        $sender_email = $user ? $user['s_email'] : osc_logged_user_email();
    } else {
        // Guest user
        if (!osc_get_preference('im_guest_messaging', 'instant_messenger')) {
            echo json_encode(array('status' => 'error', 'error' => __('Please log in or register to contact the seller.', 'instant_messenger')));
            exit;
        }

        $sender_id = 0;
        $sender_name = trim(strip_tags((string)Params::getParam('yourName')));
        $sender_email = trim(strip_tags((string)Params::getParam('yourEmail')));

        if (empty($sender_name) || empty($sender_email) || !filter_var($sender_email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(array('status' => 'error', 'error' => __('Please provide a valid name and email address.', 'instant_messenger')));
            exit;
        }
    }

    if (empty($message) && (!$file || !is_uploaded_file($file['tmp_name']))) {
        echo json_encode(array('status' => 'error', 'error' => __('Please type a message to the seller.', 'instant_messenger')));
        exit;
    }

    $title = isset($item['s_title']) ? $item['s_title'] : '';
    if (empty($title)) {
        $desc = Item::newInstance()->findByPrimaryKey($item_id);
        $title = !empty($desc['s_title']) ? $desc['s_title'] : __('Listing inquiry', 'instant_messenger');
    }

    $thread_id = $im->getOrCreateThread(
        $item_id,
        $sender_id,
        $sender_name,
        $sender_email,
        $seller_id,
        $seller_name,
        $seller_email,
        $title
    );

    if ($thread_id) {
        // Post first message
        $im->sendMessage($thread_id, $sender_id, $sender_name, $sender_email, $message, $file);

        // Fetch thread details for redirect URL
        $thread = $im->getThread($thread_id);

        if ($current_user_id > 0) {
            $redirect_url = osc_route_url('im-inbox', array('thread_id' => $thread_id));
        } else {
            $redirect_url = osc_base_url() . '?page=custom&file=instant_messenger/guest_chat.php&t=' . $thread['secret_token'];
        }

        echo json_encode(array(
            'status'       => 'ok',
            'thread_id'    => $thread_id,
            'redirect_url' => $redirect_url
        ));
    } else {
        echo json_encode(array('status' => 'error', 'error' => __('Could not initiate chat thread.', 'instant_messenger')));
    }
    exit;
}
