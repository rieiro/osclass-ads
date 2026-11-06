<?php
if (!defined('ABS_PATH')) exit('ABS_PATH is not loaded. Direct access is not allowed.');

$item_id = osc_item_id();
$seller_id = osc_item_user_id();
$current_user_id = osc_is_web_user_logged_in() ? (int)osc_logged_user_id() : 0;

// Don't show button to listing owner
if ($current_user_id > 0 && $current_user_id === (int)$seller_id) {
    return;
}

$is_logged_in = osc_is_web_user_logged_in();
$guest_enabled = osc_get_preference('im_guest_messaging', 'instant_messenger');
$ajax_url = osc_base_url(true) . '?page=ajax';
?>

<div class="im-item-action-wrap" style="margin-top: 10px; margin-bottom: 12px;">
    <button type="button" class="im-item-chat-btn" id="imOpenModalBtn">
        <i class="fa fa-comments"></i>
        <span><?php _e('Chat with Seller', 'instant_messenger'); ?></span>
    </button>
</div>

<!-- Modal Popup for Chat Initiation -->
<div class="im-modal-backdrop" id="imModalBackdrop">
    <div class="im-modal">
        <div class="im-modal-header">
            <h3><i class="fa fa-comment-dots" style="color: #0284c7; margin-right: 6px;"></i> <?php echo sprintf(__('Chat with %s', 'instant_messenger'), osc_esc_html(osc_item_contact_name())); ?></h3>
            <button type="button" class="im-modal-close" id="imModalClose">&times;</button>
        </div>
        <form id="imStartChatForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="im_start_chat" />
            <input type="hidden" name="item_id" value="<?php echo (int)$item_id; ?>" />

            <div class="im-modal-body">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px; font-size: 13px; color: #475569;">
                    <strong><?php echo osc_esc_html(osc_item_title()); ?></strong>
                    <div style="color: #16a34a; font-weight: 700; margin-top: 2px;">
                        <?php echo osc_item_formated_price(); ?>
                    </div>
                </div>

                <?php if (!$is_logged_in): ?>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;"><?php _e('Your Name', 'instant_messenger'); ?> *</label>
                            <input type="text" name="yourName" required placeholder="<?php echo osc_esc_html(__('e.g. John Doe', 'instant_messenger')); ?>" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px; font-size: 13px; box-sizing: border-box;" />
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;"><?php _e('Your Email', 'instant_messenger'); ?> *</label>
                            <input type="email" name="yourEmail" required placeholder="<?php echo osc_esc_html(__('e.g. john@example.com', 'instant_messenger')); ?>" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px; font-size: 13px; box-sizing: border-box;" />
                        </div>
                    </div>
                    <div style="font-size: 11px; color: #64748b; margin-top: -8px; margin-bottom: 14px;">
                        <?php _e('A secure magic link will be sent to your email so you can continue the chat anytime.', 'instant_messenger'); ?>
                    </div>
                <?php endif; ?>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;"><?php _e('Message', 'instant_messenger'); ?> *</label>
                    <textarea name="message" id="imModalMessage" required rows="4" placeholder="<?php echo osc_esc_html(__('Hello! Is this item still available?', 'instant_messenger')); ?>" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px; font-size: 14px; font-family: inherit; box-sizing: border-box;"></textarea>
                </div>



                <div id="imModalError" style="display: none; padding: 8px 12px; background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; border-radius: 6px; font-size: 12px; margin-top: 10px;"></div>
            </div>

            <div class="im-modal-footer">
                <button type="button" class="btn" id="imModalCancel" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;"><?php _e('Cancel', 'instant_messenger'); ?></button>
                <button type="submit" class="btn btn-submit" id="imModalSubmit" style="background: #0284c7; color: #ffffff; font-weight: 700; padding: 8px 20px;">
                    <i class="fa fa-paper-plane"></i> <?php _e('Start Chat', 'instant_messenger'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var openBtn = document.getElementById('imOpenModalBtn');
    var backdrop = document.getElementById('imModalBackdrop');
    var closeBtn = document.getElementById('imModalClose');
    var cancelBtn = document.getElementById('imModalCancel');
    var form = document.getElementById('imStartChatForm');
    var submitBtn = document.getElementById('imModalSubmit');
    var errorBox = document.getElementById('imModalError');

    if (openBtn && backdrop) {
        openBtn.addEventListener('click', function(e) {
            e.preventDefault();
            backdrop.style.display = 'flex';
            setTimeout(function() {
                var txt = document.getElementById('imModalMessage');
                if (txt) txt.focus();
            }, 100);
        });

        function closeModal() {
            backdrop.style.display = 'none';
            if (errorBox) errorBox.style.display = 'none';
        }

        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
        backdrop.addEventListener('click', function(e) {
            if (e.target === backdrop) closeModal();
        });

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(form);
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> <?php echo osc_esc_js(__('Sending...', 'instant_messenger')); ?>';
                }
                if (errorBox) errorBox.style.display = 'none';

                fetch("<?php echo $ajax_url; ?>", {
                    method: 'POST',
                    body: formData
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.status === 'ok' && data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="fa fa-paper-plane"></i> <?php echo osc_esc_js(__('Start Chat', 'instant_messenger')); ?>';
                        }
                        if (errorBox) {
                            errorBox.textContent = data.error || 'Failed to start chat.';
                            errorBox.style.display = 'block';
                        }
                    }
                })
                .catch(function(err) {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fa fa-paper-plane"></i> <?php echo osc_esc_js(__('Start Chat', 'instant_messenger')); ?>';
                    }
                    if (errorBox) {
                        errorBox.textContent = 'Network error while initiating chat.';
                        errorBox.style.display = 'block';
                    }
                });
            });
        }
    }
});
</script>
