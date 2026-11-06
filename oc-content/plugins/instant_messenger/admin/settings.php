<?php
if (!defined('OC_ADMIN') || OC_ADMIN !== true) exit('Access denied');
if (!osc_is_admin_user_logged_in()) exit('Access denied');

$base_url = osc_admin_render_plugin_url('instant_messenger/admin/settings.php');

// Handle Post Form
if (Params::getParam('subaction') === 'save_settings') {
    osc_csrf_check();

    $guest_msg = Params::getParam('im_guest_messaging') ? '1' : '0';
    $attachments = Params::getParam('im_attachments_enabled') ? '1' : '0';
    $allowed_ext = trim((string)Params::getParam('im_allowed_ext'));
    $max_size = max(1, min(50, (int)Params::getParam('im_max_file_size')));
    $poll_interval = max(2, min(60, (int)Params::getParam('im_poll_interval')));
    $email_notif = Params::getParam('im_email_notifications') ? '1' : '0';
    $header_badge = Params::getParam('im_header_badge') ? '1' : '0';

    osc_set_preference('im_guest_messaging', $guest_msg, 'instant_messenger', 'BOOLEAN');
    osc_set_preference('im_attachments_enabled', $attachments, 'instant_messenger', 'BOOLEAN');
    osc_set_preference('im_allowed_ext', $allowed_ext, 'instant_messenger', 'STRING');
    osc_set_preference('im_max_file_size', (string)$max_size, 'instant_messenger', 'INTEGER');
    osc_set_preference('im_poll_interval', (string)$poll_interval, 'instant_messenger', 'INTEGER');
    osc_set_preference('im_email_notifications', $email_notif, 'instant_messenger', 'BOOLEAN');
    osc_set_preference('im_header_badge', $header_badge, 'instant_messenger', 'BOOLEAN');

    osc_add_flash_ok_message(__('Instant Messenger settings updated successfully.', 'instant_messenger'), 'admin');
    ob_end_clean();
    header('Location: ' . $base_url);
    exit;
}

$guest_msg     = osc_get_preference('im_guest_messaging', 'instant_messenger');
$attachments   = osc_get_preference('im_attachments_enabled', 'instant_messenger');
$allowed_ext   = osc_get_preference('im_allowed_ext', 'instant_messenger');
$max_size      = osc_get_preference('im_max_file_size', 'instant_messenger');
$poll_interval = osc_get_preference('im_poll_interval', 'instant_messenger');
$email_notif   = osc_get_preference('im_email_notifications', 'instant_messenger');
$header_badge  = osc_get_preference('im_header_badge', 'instant_messenger');

osc_current_admin_theme_path('parts/header.php');
?>
<style>
.im-adm-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.im-adm-tabs { display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; }
.im-adm-tab { padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none; color: #64748b; }
.im-adm-tab.active { background: #0284c7; color: #ffffff; }
</style>

<div style="padding: 10px 0 30px 0;">
    <div class="im-adm-header">
        <div>
            <h2 class="render-title" style="margin: 0; font-size: 22px; font-weight: 700; color: #0f172a;">
                <i class="fa fa-sliders-h" style="color: #0284c7; margin-right: 6px;"></i>
                <?php _e('Instant Messenger Configuration', 'instant_messenger'); ?>
            </h2>
            <p style="margin: 4px 0 0 0; color: #64748b; font-size: 13px;">
                <?php _e('Configure live chat behavior, attachments, guest access, and notification rules.', 'instant_messenger'); ?>
            </p>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="im-adm-tabs">
        <a href="<?php echo osc_admin_render_plugin_url('instant_messenger/admin/threads.php'); ?>" class="im-adm-tab">
            <i class="fa fa-list"></i> <?php _e('All Conversations', 'instant_messenger'); ?>
        </a>
        <a href="<?php echo $base_url; ?>" class="im-adm-tab active">
            <i class="fa fa-sliders-h"></i> <?php _e('Configuration', 'instant_messenger'); ?>
        </a>
    </div>

    <div class="form-container">
        <form method="post" action="<?php echo osc_admin_base_url(true); ?>">
            <input type="hidden" name="page" value="plugins" />
            <input type="hidden" name="action" value="renderplugin" />
            <input type="hidden" name="file" value="instant_messenger/admin/settings.php" />
            <input type="hidden" name="subaction" value="save_settings" />
            <?php osc_csrf_token_form(); ?>

            <fieldset>
                <div class="form-horizontal">
                    <div class="form-row">
                        <div class="form-label"><?php _e('Guest messaging', 'instant_messenger'); ?></div>
                        <div class="form-controls">
                            <div class="form-label-checkbox">
                                <label>
                                    <input type="checkbox" name="im_guest_messaging" value="1" <?php echo $guest_msg ? 'checked="checked"' : ''; ?> />
                                    <?php _e('Allow non-registered visitors to chat with sellers', 'instant_messenger'); ?>
                                </label>
                                <div class="help-box">
                                    <?php _e('When enabled, visitors who are not logged in can start chats with sellers on listing pages. They receive a secure magic token link to view and reply to the conversation.', 'instant_messenger'); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-label"><?php _e('Attachments', 'instant_messenger'); ?></div>
                        <div class="form-controls">
                            <div class="form-label-checkbox">
                                <label>
                                    <input type="checkbox" name="im_attachments_enabled" value="1" <?php echo $attachments ? 'checked="checked"' : ''; ?> />
                                    <?php _e('Enable file and image attachments in chat', 'instant_messenger'); ?>
                                </label>
                                <div class="help-box">
                                    <?php _e('Permit users to upload images (PNG, JPG, WEBP) and document files directly inside chat bubbles.', 'instant_messenger'); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-label"><?php _e('Allowed extensions', 'instant_messenger'); ?></div>
                        <div class="form-controls">
                            <input type="text" name="im_allowed_ext" class="xlarge" value="<?php echo osc_esc_html($allowed_ext); ?>" />
                            <div class="help-box"><?php _e('Comma-separated list of extensions (e.g. jpg,jpeg,png,gif,webp,pdf,doc,docx,zip).', 'instant_messenger'); ?></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-label"><?php _e('Max file size', 'instant_messenger'); ?></div>
                        <div class="form-controls">
                            <input type="number" name="im_max_file_size" class="input-small" min="1" max="50" value="<?php echo (int)$max_size; ?>" /> MB
                            <div class="help-box"><?php _e('Maximum size in megabytes per uploaded attachment.', 'instant_messenger'); ?></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-label"><?php _e('Polling interval', 'instant_messenger'); ?></div>
                        <div class="form-controls">
                            <input type="number" name="im_poll_interval" class="input-small" min="2" max="60" value="<?php echo (int)$poll_interval; ?>" /> <?php _e('seconds', 'instant_messenger'); ?>
                            <div class="help-box"><?php _e('How often the browser checks for new incoming messages during an active chat (Recommended: 3 to 5 seconds).', 'instant_messenger'); ?></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-label"><?php _e('Email alerts', 'instant_messenger'); ?></div>
                        <div class="form-controls">
                            <div class="form-label-checkbox">
                                <label>
                                    <input type="checkbox" name="im_email_notifications" value="1" <?php echo $email_notif ? 'checked="checked"' : ''; ?> />
                                    <?php _e('Send email notifications for new messages', 'instant_messenger'); ?>
                                </label>
                                <div class="help-box">
                                    <?php _e('Alert participants via transactional email when a new message arrives with direct link to the chat thread.', 'instant_messenger'); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-label"><?php _e('Header unread badge', 'instant_messenger'); ?></div>
                        <div class="form-controls">
                            <div class="form-label-checkbox">
                                <label>
                                    <input type="checkbox" name="im_header_badge" value="1" <?php echo $header_badge ? 'checked="checked"' : ''; ?> />
                                    <?php _e('Display unread messages badge counter in header & menu', 'instant_messenger'); ?>
                                </label>
                                <div class="help-box">
                                    <?php _e('Shows real-time red badge counter of unread messages in user account menu and navbar.', 'instant_messenger'); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions" style="margin-top: 25px;">
                        <input type="submit" value="<?php echo osc_esc_html(__('Save changes')); ?>" class="btn btn-submit" />
                    </div>
                </div>
            </fieldset>
        </form>
    </div>
</div>

<?php 
osc_current_admin_theme_path('parts/footer.php');
