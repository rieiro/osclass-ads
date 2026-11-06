<?php
if (!defined('ABS_PATH')) exit('ABS_PATH is not loaded. Direct access is not allowed.');

if (!osc_is_admin_user_logged_in()) {
    die('Access denied');
}

$conn = DBConnectionClass::newInstance();
$db   = $conn->getOsclassDb();
$comm = new DBCommandClass($db);
$bp = BusinessProfile::newInstance();

// Handle Actions (Verify / Unverify)
$action = Params::getParam('bp_action');
$targetUserId = (int)Params::getParam('target_user_id');

if ($action === 'toggle_verify' && $targetUserId > 0) {
    if (function_exists('osc_csrf_check')) {
        osc_csrf_check();
    }
    $isCurrentlyVerified = $bp->isVerified($targetUserId);
    $bp->setVerified($targetUserId, !$isCurrentlyVerified);
    osc_add_flash_ok_message(__('Verification status updated successfully.', 'business_profile'), 'admin');
    header('Location: ' . osc_admin_render_plugin_url('business_profile/admin.php'));
    if (!defined('OC_TEST_MODE') || !OC_TEST_MODE) {
        exit;
    }
    return;
}

// Fetch all profiles
$search = trim((string)Params::getParam('sSearch'));
$sql = "SELECT bp.*, u.s_name AS user_name, u.s_email AS user_email 
        FROM " . DB_TABLE_PREFIX . "t_business_profile bp
        JOIN " . DB_TABLE_PREFIX . "t_user u ON u.pk_i_id = bp.fk_i_user_id";

if (!empty($search)) {
    $esc = $conn->getOsclassDb()->real_escape_string($search);
    $sql .= " WHERE bp.s_company_name LIKE '%{$esc}%' OR bp.s_slug LIKE '%{$esc}%'";
}
$sql .= " ORDER BY bp.dt_created_at DESC";

$res = $comm->query($sql);
$profiles = ($res && $res->numRows() > 0) ? $res->result() : [];
?>

<div class="wrap" style="padding: 20px;">
    <h2>Business Profiles Moderation</h2>
    <p>Manage verified stores, seller directories, and storefront listings.</p>

    <!-- Filter form -->
    <form method="get" action="<?php echo osc_admin_base_url(true); ?>" style="margin-bottom: 20px; display: flex; gap: 10px;">
        <input type="hidden" name="page" value="plugins" />
        <input type="hidden" name="action" value="renderplugin" />
        <input type="hidden" name="file" value="business_profile/admin.php" />
        <input type="text" name="sSearch" value="<?php echo osc_esc_html($search); ?>" placeholder="Search company or slug..." style="padding: 6px 12px; width: 260px;" />
        <button type="submit" class="btn btn-submit">Filter</button>
    </form>

    <table class="table" style="width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <thead>
            <tr style="background: #f1f5f9; text-align: left;">
                <th style="padding: 10px;">Store Name</th>
                <th style="padding: 10px;">Owner</th>
                <th style="padding: 10px;">Slug</th>
                <th style="padding: 10px;">WhatsApp</th>
                <th style="padding: 10px;">Status</th>
                <th style="padding: 10px;">Verification</th>
                <th style="padding: 10px; text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($profiles)): ?>
                <tr>
                    <td colspan="7" style="padding: 20px; text-align: center; color: #64748b;">No business profiles found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($profiles as $p): ?>
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 10px; font-weight: bold;"><?php echo osc_esc_html($p['s_company_name']); ?></td>
                        <td style="padding: 10px;"><?php echo osc_esc_html($p['user_name'] . ' (' . $p['user_email'] . ')'); ?></td>
                        <td style="padding: 10px;">
                            <a href="<?php echo osc_base_url(true); ?>?page=business&slug=<?php echo urlencode($p['s_slug']); ?>" target="_blank" style="color: #4f46e5;">
                                <?php echo osc_esc_html($p['s_slug']); ?> &rarr;
                            </a>
                        </td>
                        <td style="padding: 10px;"><?php echo osc_esc_html($p['s_whatsapp'] ?: '-'); ?></td>
                        <td style="padding: 10px;">
                            <?php if ((int)$p['b_enabled'] === 1): ?>
                                <span class="badge" style="background: #10b981; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 11px;">Active</span>
                            <?php else: ?>
                                <span class="badge" style="background: #ef4444; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 11px;">Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 10px;">
                            <?php if ((int)$p['b_verified'] === 1): ?>
                                <strong style="color: #16a34a;">&#10004; Verified</strong>
                            <?php else: ?>
                                <span style="color: #64748b;">Unverified</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 10px; text-align: right;">
                            <a href="<?php echo osc_admin_render_plugin_url('business_profile/admin.php'); ?>&bp_action=toggle_verify&target_user_id=<?php echo (int)$p['fk_i_user_id']; ?>&<?php echo osc_csrf_token_url(); ?>" class="btn btn-mini" style="background: <?php echo ((int)$p['b_verified'] === 1 ? '#f59e0b' : '#22c55e'); ?>; color: #fff; padding: 4px 8px; border-radius: 4px; text-decoration: none; font-size: 12px;">
                                <?php echo ((int)$p['b_verified'] === 1 ? 'Revoke Verification' : 'Grant Verified Badge'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
