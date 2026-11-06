<?php
if (!defined('OC_ADMIN') || OC_ADMIN !== true) exit('Access denied');
if (!osc_is_admin_user_logged_in()) exit('Access denied');

$conn = DBConnectionClass::newInstance();
$db   = $conn->getOsclassDb();
$comm = new DBCommandClass($db);
$im   = InstantMessenger::newInstance();

$base_url = osc_admin_render_plugin_url('instant_messenger/admin/threads.php');

// Subaction Handling
$subaction = trim((string)Params::getParam('subaction'));
if ($subaction === 'delete_thread') {
    osc_csrf_check();
    $th_id = (int)Params::getParam('thread_id');
    if ($th_id > 0) {
        $im->deleteThread($th_id);
        osc_add_flash_ok_message(sprintf(__('Thread #%d and all its messages/attachments were deleted.', 'instant_messenger'), $th_id), 'admin');
    }
    ob_end_clean();
    header('Location: ' . $base_url);
    exit;
}

// Search Filter & Pagination
$sSearch  = trim((string)Params::getParam('sSearch'));
$page_num = max(1, (int)Params::getParam('iPage'));
$per_page = 25;
$offset   = ($page_num - 1) * $per_page;

$where = "1=1";
if ($sSearch !== '') {
    $esc = $comm->escape('%' . $sSearch . '%');
    $where .= " AND (title LIKE $esc OR sender_name LIKE $esc OR sender_email LIKE $esc OR receiver_name LIKE $esc OR receiver_email LIKE $esc OR last_message LIKE $esc)";
}

$count_res = $comm->query("SELECT COUNT(*) as total FROM " . DB_TABLE_PREFIX . "t_im_threads WHERE " . $where);
$total_threads = $count_res ? (int)$count_res->row()['total'] : 0;
$total_pages = max(1, (int)ceil($total_threads / $per_page));

$threads_sql = "SELECT t.*, 
                (SELECT COUNT(*) FROM " . DB_TABLE_PREFIX . "t_im_messages WHERE thread_id = t.id) as total_messages
                FROM " . DB_TABLE_PREFIX . "t_im_threads t
                WHERE $where
                ORDER BY t.updated_at DESC
                LIMIT $offset, $per_page";

$threads_res = $comm->query($threads_sql);
$threads = $threads_res ? $threads_res->result() : array();

osc_current_admin_theme_path('parts/header.php');
?>
<style>
.im-adm-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.im-adm-tabs { display: flex; gap: 8px; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; }
.im-adm-tab { padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none; color: #64748b; }
.im-adm-tab.active { background: #0284c7; color: #ffffff; }
.im-table { width: 100%; border-collapse: collapse; font-size: 13px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
.im-table th { background: #f8fafc; color: #475569; font-weight: 600; padding: 12px 14px; border-bottom: 1px solid #e2e8f0; text-align: left; }
.im-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.im-table tr:hover td { background-color: #f8fafc; }
</style>

<div style="padding: 10px 0 30px 0;">
    <div class="im-adm-header">
        <div>
            <h2 class="render-title" style="margin: 0; font-size: 22px; font-weight: 700; color: #0f172a;">
                <i class="fa fa-comments" style="color: #0284c7; margin-right: 6px;"></i>
                <?php _e('Instant Messenger Conversations', 'instant_messenger'); ?>
            </h2>
            <p style="margin: 4px 0 0 0; color: #64748b; font-size: 13px;">
                <?php _e('Audit, monitor, and moderate live customer and seller chat threads.', 'instant_messenger'); ?>
            </p>
        </div>
        <div>
            <a href="<?php echo osc_admin_render_plugin_url('instant_messenger/admin/settings.php'); ?>" class="btn" style="background: #ffffff; border: 1px solid #cbd5e1; color: #334155; font-weight: 600;">
                <i class="fa fa-cog"></i> <?php _e('Messenger Settings', 'instant_messenger'); ?>
            </a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="im-adm-tabs">
        <a href="<?php echo $base_url; ?>" class="im-adm-tab active">
            <i class="fa fa-list"></i> <?php _e('All Conversations', 'instant_messenger'); ?> (<?php echo number_format($total_threads); ?>)
        </a>
        <a href="<?php echo osc_admin_render_plugin_url('instant_messenger/admin/settings.php'); ?>" class="im-adm-tab">
            <i class="fa fa-sliders-h"></i> <?php _e('Configuration', 'instant_messenger'); ?>
        </a>
    </div>

    <!-- Search Toolbar -->
    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px;">
        <form method="get" action="<?php echo osc_admin_base_url(true); ?>" style="display: flex; gap: 10px; align-items: center;">
            <input type="hidden" name="page" value="plugins" />
            <input type="hidden" name="action" value="renderplugin" />
            <input type="hidden" name="file" value="instant_messenger/admin/threads.php" />

            <input type="text" name="sSearch" value="<?php echo osc_esc_html($sSearch); ?>" placeholder="<?php echo osc_esc_html(__('Search by buyer, seller, email, or listing title...', 'instant_messenger')); ?>" style="flex: 1; height: 36px; padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;" />
            <button type="submit" class="btn btn-submit" style="background: #0284c7; color: #fff; height: 36px; padding: 0 16px; font-weight: 600;">
                <i class="fa fa-search"></i> <?php _e('Search', 'instant_messenger'); ?>
            </button>
            <?php if ($sSearch !== ''): ?>
                <a href="<?php echo $base_url; ?>" class="btn" style="height: 36px; line-height: 24px; background: #f1f5f9; color: #475569;"><?php _e('Reset', 'instant_messenger'); ?></a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Data Table -->
    <table class="im-table">
        <thead>
            <tr>
                <th style="width: 50px;">#</th>
                <th><?php _e('Listing / Subject', 'instant_messenger'); ?></th>
                <th style="width: 200px;"><?php _e('Buyer (Initiator)', 'instant_messenger'); ?></th>
                <th style="width: 200px;"><?php _e('Seller (Recipient)', 'instant_messenger'); ?></th>
                <th style="width: 90px; text-align: center;"><?php _e('Messages', 'instant_messenger'); ?></th>
                <th style="width: 140px;"><?php _e('Last Activity', 'instant_messenger'); ?></th>
                <th style="width: 140px; text-align: right;"><?php _e('Actions', 'instant_messenger'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($threads)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;">
                        <i class="fa fa-comments" style="font-size: 32px; margin-bottom: 8px; display: block; opacity: 0.5;"></i>
                        <?php _e('No conversation threads found.', 'instant_messenger'); ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($threads as $t): 
                    $is_guest = ((int)$t['sender_id'] === 0);
                    $del_url = $base_url . '&subaction=delete_thread&thread_id=' . $t['id'] . '&' . osc_csrf_token_url();
                ?>
                    <tr>
                        <td style="font-weight: 600; color: #94a3b8;"><?php echo $t['id']; ?></td>
                        <td>
                            <strong style="color: #0f172a; font-size: 14px;"><?php echo osc_esc_html($t['title']); ?></strong>
                            <?php if ($t['item_id'] > 0): ?>
                                <div><a href="<?php echo osc_item_url_ns($t['item_id']); ?>" target="_blank" style="color: #0284c7; font-size: 11px;"><i class="fa fa-external-link-alt"></i> <?php _e('View Listing', 'instant_messenger'); ?></a></div>
                            <?php endif; ?>
                            <div style="color: #64748b; font-size: 12px; margin-top: 2px;">
                                <em><?php echo osc_esc_html($t['last_message']); ?></em>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #0f172a;"><?php echo osc_esc_html($t['sender_name']); ?></div>
                            <div style="font-size: 11px; color: #64748b;"><?php echo osc_esc_html($t['sender_email']); ?></div>
                            <?php if ($is_guest): ?>
                                <span style="background: #fef3c7; color: #92400e; padding: 1px 6px; border-radius: 10px; font-size: 10px; font-weight: 700;"><?php _e('Guest', 'instant_messenger'); ?></span>
                            <?php else: ?>
                                <span style="background: #e0f2fe; color: #0369a1; padding: 1px 6px; border-radius: 10px; font-size: 10px; font-weight: 700;"><?php _e('Registered', 'instant_messenger'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #0f172a;"><?php echo osc_esc_html($t['receiver_name']); ?></div>
                            <div style="font-size: 11px; color: #64748b;"><?php echo osc_esc_html($t['receiver_email']); ?></div>
                        </td>
                        <td style="text-align: center;">
                            <span style="background: #f1f5f9; color: #334155; padding: 3px 8px; border-radius: 12px; font-weight: 700; font-size: 12px;">
                                <?php echo $t['total_messages']; ?>
                            </span>
                        </td>
                        <td style="font-size: 12px; color: #64748b;">
                            <?php echo date('M d, Y H:i', strtotime($t['updated_at'])); ?>
                        </td>
                        <td style="text-align: right;">
                            <a href="<?php echo $del_url; ?>" class="btn" onclick="return confirm('<?php echo osc_esc_js(__('Are you sure you want to permanently delete this entire conversation?', 'instant_messenger')); ?>');" style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; font-size: 11px; padding: 4px 8px;">
                                <i class="fa fa-trash"></i> <?php _e('Delete', 'instant_messenger'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; font-size: 13px; color: #64748b;">
            <div>
                <?php echo sprintf(__('Showing page %d of %d (%d threads)', 'instant_messenger'), $page_num, $total_pages, $total_threads); ?>
            </div>
            <div style="display: flex; gap: 4px;">
                <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                    <a href="<?php echo $base_url . '&iPage=' . $p . '&sSearch=' . urlencode($sSearch); ?>" style="padding: 4px 10px; border: 1px solid #cbd5e1; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: 600; <?php echo ($p === $page_num) ? 'background:#0284c7;color:#fff;border-color:#0284c7;' : 'background:#fff;color:#334155;'; ?>">
                        <?php echo $p; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php 
osc_current_admin_theme_path('parts/footer.php');
