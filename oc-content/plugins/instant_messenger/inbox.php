<?php
if (!defined('ABS_PATH')) exit('ABS_PATH is not loaded. Direct access is not allowed.');

if (!osc_is_web_user_logged_in()) {
    osc_add_flash_error_message(__('Please log in to view your messages.', 'instant_messenger'));
    header('Location: ' . osc_user_login_url());
    exit;
}

$im = InstantMessenger::newInstance();
$current_user_id = (int)osc_logged_user_id();

// Fetch user threads
$threads = $im->getUserThreads($current_user_id);
$active_thread_id = (int)Params::getParam('thread_id');

if ($active_thread_id <= 0 && !empty($threads)) {
    $active_thread_id = (int)$threads[0]['id'];
}

$active_thread = null;
if ($active_thread_id > 0) {
    foreach ($threads as $t) {
        if ((int)$t['id'] === $active_thread_id) {
            $active_thread = $t;
            break;
        }
    }
    // If not found in initial 50, fetch directly
    if (!$active_thread) {
        $active_thread = $im->getThread($active_thread_id);
    }
}

// Fetch messages for active thread
$messages = array();
$last_message_id = 0;
$listing = null;
$other_name = '';

if ($active_thread) {
    $im->markThreadRead($active_thread_id, $current_user_id);
    $messages = $im->getMessages($active_thread_id);
    if (!empty($messages)) {
        $last = end($messages);
        $last_message_id = (int)$last['id'];
    }

    if (!empty($active_thread['item_id'])) {
        $listing = Item::newInstance()->findByPrimaryKey($active_thread['item_id']);
    }

    // Determine the other user's name
    if ((int)$active_thread['sender_id'] === $current_user_id) {
        $other_name = !empty($active_thread['receiver_name']) ? $active_thread['receiver_name'] : __('Seller', 'instant_messenger');
    } else {
        $other_name = !empty($active_thread['sender_name']) ? $active_thread['sender_name'] : __('Buyer', 'instant_messenger');
    }
}

$base_upload_url = osc_base_url() . 'oc-content/uploads/instant_messenger/';
$ajax_url = osc_base_url(true) . '?page=ajax';
$poll_interval = (int)osc_get_preference('im_poll_interval', 'instant_messenger') * 1000;
if ($poll_interval <= 0) $poll_interval = 4000;
?>

<div class="wrapper" style="margin-top: 20px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
        <h1 style="font-size: 24px; font-weight: 700; color: #0f172a; margin: 0;">
            <i class="fa fa-comments" style="color: #0284c7; margin-right: 6px;"></i>
            <?php _e('Instant Messages', 'instant_messenger'); ?>
        </h1>
        <span style="font-size: 13px; color: #64748b;">
            <?php echo sprintf(__('%d Conversations', 'instant_messenger'), count($threads)); ?>
        </span>
    </div>

    <?php if (empty($threads)): ?>
        <div style="text-align: center; padding: 60px 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; margin-top: 15px;">
            <i class="fa fa-comment-dots" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px;"></i>
            <h2 style="font-size: 18px; color: #334155; margin-bottom: 8px;"><?php _e('No conversations yet', 'instant_messenger'); ?></h2>
            <p style="color: #64748b; font-size: 14px; max-width: 400px; margin: 0 auto 20px auto;">
                <?php _e('When you contact a seller or receive inquiries on your listings, your chat threads will appear here.', 'instant_messenger'); ?>
            </p>
            <a href="<?php echo osc_base_url(); ?>" class="btn btn-primary" style="background: #0284c7; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: 600;">
                <?php _e('Browse Listings', 'instant_messenger'); ?>
            </a>
        </div>
    <?php else: ?>
        <div class="im-container">
            <!-- Sidebar: Threads List -->
            <div class="im-sidebar">
                <div class="im-sidebar-header">
                    <span><?php _e('Chats', 'instant_messenger'); ?></span>
                </div>
                <div class="im-thread-list">
                    <?php foreach ($threads as $th): 
                        $is_active = ((int)$th['id'] === $active_thread_id);
                        $is_me_sender = ((int)$th['sender_id'] === $current_user_id);
                        $th_other_name = $is_me_sender ? (!empty($th['receiver_name']) ? $th['receiver_name'] : __('Seller', 'instant_messenger')) : (!empty($th['sender_name']) ? $th['sender_name'] : __('Buyer', 'instant_messenger'));
                        $unread = $is_me_sender ? (int)$th['sender_unread'] : (int)$th['receiver_unread'];
                        $thread_link = osc_base_url(true) . '?page=custom&file=instant_messenger/inbox.php&thread_id=' . $th['id'];
                    ?>
                        <a href="<?php echo $thread_link; ?>" class="im-thread-item <?php echo $is_active ? 'active' : ''; ?>">
                            <div class="im-avatar">
                                <?php echo strtoupper(mb_substr($th_other_name, 0, 1)); ?>
                            </div>
                            <div class="im-thread-info">
                                <div class="im-thread-name"><?php echo osc_esc_html($th_other_name); ?></div>
                                <?php if (!empty($th['title'])): ?>
                                    <div class="im-thread-item-title"><i class="fa fa-tag" style="font-size: 10px;"></i> <?php echo osc_esc_html($th['title']); ?></div>
                                <?php endif; ?>
                                <div class="im-thread-snippet"><?php echo !empty($th['last_message']) ? osc_esc_html($th['last_message']) : '<em>' . __('No messages yet', 'instant_messenger') . '</em>'; ?></div>
                            </div>
                            <div class="im-thread-meta">
                                <div><?php echo date('M d', strtotime($th['updated_at'])); ?></div>
                                <?php if ($unread > 0): ?>
                                    <span class="im-unread-pill"><?php echo $unread; ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Main: Active Chat Stream -->
            <div class="im-main">
                <?php if ($active_thread): ?>
                    <!-- Chat Header -->
                    <div class="im-header">
                        <div class="im-header-user">
                            <div class="im-avatar" style="width: 40px; height: 40px; font-size: 15px;">
                                <?php echo strtoupper(mb_substr($other_name, 0, 1)); ?>
                            </div>
                            <div>
                                <div style="font-weight: 700; font-size: 15px; color: #0f172a;"><?php echo osc_esc_html($other_name); ?></div>
                                <div style="font-size: 12px; color: #16a34a;"><i class="fa fa-circle" style="font-size: 8px;"></i> <?php _e('Active conversation', 'instant_messenger'); ?></div>
                            </div>
                        </div>

                        <?php if ($listing): 
                            $listing_url = osc_item_url_ns($listing['pk_i_id']);
                            $listing_img = null;
                            $res_img = ItemResource::newInstance()->getResource($listing['pk_i_id']);
                            if ($res_img && !empty($res_img['s_name'])) {
                                $listing_img = osc_apply_filter('resource_path', osc_base_url() . $res_img['s_path']) . $res_img['pk_i_id'] . '_thumbnail.' . $res_img['s_extension'];
                            }
                        ?>
                            <a href="<?php echo $listing_url; ?>" target="_blank" class="im-header-listing" title="<?php _e('View listing in new tab', 'instant_messenger'); ?>">
                                <?php if ($listing_img): ?>
                                    <img src="<?php echo $listing_img; ?>" alt="" />
                                <?php else: ?>
                                    <div style="width: 36px; height: 36px; background: #e2e8f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #94a3b8;"><i class="fa fa-image"></i></div>
                                <?php endif; ?>
                                <div style="overflow: hidden;">
                                    <div class="im-header-listing-title"><?php echo osc_esc_html($active_thread['title']); ?></div>
                                    <div class="im-header-listing-price"><?php echo osc_format_price($listing['i_price'], $listing['fk_c_currency_code']); ?></div>
                                </div>
                                <i class="fa fa-external-link-alt" style="font-size: 11px; color: #94a3b8; margin-left: 6px;"></i>
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Message Stream -->
                    <div class="im-messages" id="imMessagesBox">
                        <?php foreach ($messages as $m): 
                            $is_me = ((int)$m['sender_id'] === $current_user_id);
                            $att_url = !empty($m['attachment']) ? $base_upload_url . $m['attachment'] : null;
                            $ext = $att_url ? strtolower(pathinfo($m['attachment'], PATHINFO_EXTENSION)) : '';
                            $is_img = in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'));
                        ?>
                            <div class="im-msg-row <?php echo $is_me ? 'me' : 'other'; ?>" id="imMsg_<?php echo (int)$m['id']; ?>" data-msg-id="<?php echo (int)$m['id']; ?>">
                                <div class="im-msg-bubble">
                                    <?php if (!empty($m['message'])): ?>
                                        <div><?php echo nl2br(htmlspecialchars($m['message'])); ?></div>
                                    <?php endif; ?>

                                    <?php if ($att_url): ?>
                                        <?php if ($is_img): ?>
                                            <div class="im-attachment-preview">
                                                <a href="<?php echo $att_url; ?>" target="_blank">
                                                    <img src="<?php echo $att_url; ?>" alt="attachment" />
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <div>
                                                <a href="<?php echo $att_url; ?>" target="_blank" class="im-attachment-doc">
                                                    <i class="fa fa-file"></i> <?php echo osc_esc_html($m['attachment_original']); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="im-msg-meta">
                                    <span><?php echo date('H:i', strtotime($m['created_at'])); ?></span>
                                    <?php if ($is_me): ?>
                                        <span class="im-seen-check">
                                            <?php if ((int)$m['is_read'] === 1): ?>
                                                <i class="fa fa-check-double"></i> <?php _e('Seen', 'instant_messenger'); ?>
                                            <?php else: ?>
                                                <i class="fa fa-check"></i>
                                            <?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Input Bar -->
                    <div class="im-input-bar">
                        <textarea id="imMessageInput" style="overflow-y: hidden; resize: none;" placeholder="<?php echo osc_esc_html(__('Type your message... (Press Enter to send)', 'instant_messenger')); ?>"></textarea>
                        
                        <button type="button" class="im-send-btn" id="imSendBtn" title="<?php _e('Send', 'instant_messenger'); ?>">
                            <i class="fa fa-paper-plane"></i>
                        </button>
                    </div>
                <?php else: ?>
                    <div style="flex: 1; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                        <?php _e('Select a conversation to start chatting.', 'instant_messenger'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
window.imSeenText = "<?php echo osc_esc_js(__('Seen', 'instant_messenger')); ?>";
document.addEventListener('DOMContentLoaded', function() {
    if (window.InstantMessenger && <?php echo ($active_thread ? 'true' : 'false'); ?>) {
        InstantMessenger.init({
            threadId: <?php echo (int)$active_thread_id; ?>,
            lastMessageId: <?php echo (int)$last_message_id; ?>,
            pollInterval: <?php echo (int)$poll_interval; ?>,
            ajaxUrl: "<?php echo $ajax_url; ?>"
        });
    }
});
</script>
