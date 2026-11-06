<?php
if (!defined('ABS_PATH')) exit('ABS_PATH is not loaded. Direct access is not allowed.');

$token = trim((string)Params::getParam('t'));
$im = InstantMessenger::newInstance();

$thread = $im->getThreadByToken($token);
if (!$thread) {
    osc_add_flash_error_message(__('Invalid or expired conversation link.', 'instant_messenger'));
    header('Location: ' . osc_base_url());
    exit;
}

$thread_id = (int)$thread['id'];
$im->markThreadRead($thread_id, 0, $thread['sender_email']);
$messages = $im->getMessages($thread_id);
$last_message_id = 0;
if (!empty($messages)) {
    $last = end($messages);
    $last_message_id = (int)$last['id'];
}

$listing = null;
if (!empty($thread['item_id'])) {
    $listing = Item::newInstance()->findByPrimaryKey($thread['item_id']);
}

$seller_name = !empty($thread['receiver_name']) ? $thread['receiver_name'] : __('Seller', 'instant_messenger');
$base_upload_url = osc_base_url() . 'oc-content/uploads/instant_messenger/';
$ajax_url = osc_base_url(true) . '?page=ajax&token=' . urlencode($token);
$poll_interval = (int)osc_get_preference('im_poll_interval', 'instant_messenger') * 1000;
if ($poll_interval <= 0) $poll_interval = 4000;
?>

<div class="wrapper" style="margin-top: 25px; max-width: 900px; margin-left: auto; margin-right: auto;">
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <!-- Top Banner for Guest -->
        <div style="background: #0284c7; color: #ffffff; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong style="font-size: 15px;"><i class="fa fa-comments"></i> <?php echo sprintf(__('Chat with %s', 'instant_messenger'), osc_esc_html($seller_name)); ?></strong>
                <span style="font-size: 12px; opacity: 0.9; margin-left: 8px;">(<?php _e('Guest mode', 'instant_messenger'); ?>)</span>
            </div>
            <a href="<?php echo osc_register_account_url(); ?>" style="color: #ffffff; text-decoration: underline; font-size: 12px;">
                <?php _e('Create free account to save chats &rarr;', 'instant_messenger'); ?>
            </a>
        </div>

        <?php if ($listing): 
            $listing_url = osc_item_url_ns($listing['pk_i_id']);
            $listing_img = null;
            $res_img = ItemResource::newInstance()->getResource($listing['pk_i_id']);
            if ($res_img && !empty($res_img['s_name'])) {
                $listing_img = osc_apply_filter('resource_path', osc_base_url() . $res_img['s_path']) . $res_img['pk_i_id'] . '_thumbnail.' . $res_img['s_extension'];
            }
        ?>
            <div style="padding: 12px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <?php if ($listing_img): ?>
                        <img src="<?php echo $listing_img; ?>" style="width: 44px; height: 44px; border-radius: 4px; object-fit: cover;" alt="" />
                    <?php endif; ?>
                    <div>
                        <a href="<?php echo $listing_url; ?>" target="_blank" style="font-weight: 700; color: #0f172a; text-decoration: none; font-size: 14px;">
                            <?php echo osc_esc_html($thread['title']); ?> <i class="fa fa-external-link-alt" style="font-size: 11px; color: #94a3b8;"></i>
                        </a>
                        <div style="font-weight: 700; color: #16a34a; font-size: 13px;">
                            <?php echo osc_format_price($listing['i_price'], $listing['fk_c_currency_code']); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Messages Stream -->
        <div class="im-messages" id="imMessagesBox" style="height: 480px; max-height: 60vh;">
            <?php foreach ($messages as $m): 
                $is_me = (empty($m['sender_id']) || strtolower($m['sender_email']) === strtolower($thread['sender_email']));
                $att_url = !empty($m['attachment']) ? $base_upload_url . $m['attachment'] : null;
                $ext = $att_url ? strtolower(pathinfo($m['attachment'], PATHINFO_EXTENSION)) : '';
                $is_img = in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'));
            ?>
                <div class="im-msg-row <?php echo $is_me ? 'me' : 'other'; ?>">
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

        <!-- Selected Attachment Pill -->
        <div id="imAttachBadge" style="display: none; padding: 4px 14px; background: #e0f2fe; color: #0369a1; font-size: 12px; font-weight: 600; border-top: 1px solid #bae6fd;"></div>

        <!-- Input Bar -->
        <div class="im-input-bar">
            <?php if (osc_get_preference('im_attachments_enabled', 'instant_messenger')): ?>
                <input type="file" id="imFileInput" style="display: none;" accept="image/*,.pdf,.doc,.docx,.zip" />
                <button type="button" class="im-attach-btn" id="imAttachBtn" title="<?php _e('Attach photo or file', 'instant_messenger'); ?>">
                    <i class="fa fa-paperclip"></i>
                </button>
            <?php endif; ?>

            <textarea id="imMessageInput" placeholder="<?php echo osc_esc_html(__('Type your message... (Press Enter to send)', 'instant_messenger')); ?>"></textarea>
            
            <button type="button" class="im-send-btn" id="imSendBtn" title="<?php _e('Send', 'instant_messenger'); ?>">
                <i class="fa fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

<script>
window.imSeenText = "<?php echo osc_esc_js(__('Seen', 'instant_messenger')); ?>";
document.addEventListener('DOMContentLoaded', function() {
    if (window.InstantMessenger) {
        InstantMessenger.init({
            threadId: <?php echo (int)$thread_id; ?>,
            lastMessageId: <?php echo (int)$last_message_id; ?>,
            pollInterval: <?php echo (int)$poll_interval; ?>,
            ajaxUrl: "<?php echo $ajax_url; ?>"
        });
    }
});
</script>
