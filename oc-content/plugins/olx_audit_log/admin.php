<?php
if (!defined('OC_ADMIN') || OC_ADMIN !== true) exit('Access denied');

$conn = DBConnectionClass::newInstance();
$db   = $conn->getOsclassDb();
$comm = new DBCommandClass($db);

// Filter Parameters
$sSearch    = trim((string)Params::getParam('sSearch'));
$sActorType = trim((string)Params::getParam('sActorType'));
$sAction    = trim((string)Params::getParam('sAction'));
$sStatus    = trim((string)Params::getParam('sStatus'));
$dtFrom     = trim((string)Params::getParam('dtFrom'));
$dtTo       = trim((string)Params::getParam('dtTo'));
$page_num   = max(1, (int)Params::getParam('iPage'));
$per_page   = 25;
$offset     = ($page_num - 1) * $per_page;

// Build WHERE Clause
$where_clauses = array("1=1");
if ($sSearch !== '') {
    $esc = $comm->escape('%' . $sSearch . '%');
    $where_clauses[] = "(s_actor_name LIKE $esc OR s_actor_email LIKE $esc OR s_ip_address LIKE $esc OR s_action LIKE $esc OR s_url LIKE $esc OR j_payload LIKE $esc)";
}
if ($sActorType !== '' && $sActorType !== 'all') {
    $where_clauses[] = "s_actor_type = " . $comm->escape($sActorType);
}
if ($sAction !== '' && $sAction !== 'all') {
    $where_clauses[] = "s_action LIKE " . $comm->escape($sAction . '%');
}
if ($sStatus !== '' && $sStatus !== 'all') {
    $where_clauses[] = "s_status = " . $comm->escape($sStatus);
}
if ($dtFrom !== '') {
    $where_clauses[] = "dt_created_at >= " . $comm->escape($dtFrom . ' 00:00:00');
}
if ($dtTo !== '') {
    $where_clauses[] = "dt_created_at <= " . $comm->escape($dtTo . ' 23:59:59');
}

$where_sql = implode(' AND ', $where_clauses);

// -------------------------------------------------------------
// CSV EXPORT STREAM
// -------------------------------------------------------------
if (Params::getParam('export') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=audit_log_' . date('Y-m-d_His') . '.csv');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Excel
    fputs($output, "ï»¿");
    fputcsv($output, array('ID', 'Date (UTC)', 'Actor Type', 'Actor ID', 'Actor Name', 'Actor Email', 'Action', 'Target Type', 'Target ID', 'HTTP Method', 'URL', 'IP Address', 'User Agent', 'Status', 'Payload'));

    $sql_csv = "SELECT * FROM " . DB_TABLE_PREFIX . "t_audit_log WHERE $where_sql ORDER BY pk_i_id DESC LIMIT 5000";
    $res_csv = $comm->query($sql_csv);
    if ($res_csv) {
        $rows = $res_csv->result();
        foreach ($rows as $r) {
            fputcsv($output, array(
                $r['pk_i_id'],
                $r['dt_created_at'],
                $r['s_actor_type'],
                $r['fk_i_actor_id'],
                $r['s_actor_name'],
                $r['s_actor_email'],
                $r['s_action'],
                $r['s_target_type'],
                $r['s_target_id'],
                $r['s_http_method'],
                $r['s_url'],
                $r['s_ip_address'],
                $r['s_user_agent'],
                $r['s_status'],
                $r['j_payload']
            ));
        }
    }
    fclose($output);
    exit;
}

// -------------------------------------------------------------
// METRICS QUERY
// -------------------------------------------------------------
$res_total = $comm->query("SELECT count(*) as total FROM " . DB_TABLE_PREFIX . "t_audit_log");
$total_logs = $res_total ? (int)$res_total->row()['total'] : 0;

$res_admin = $comm->query("SELECT count(*) as total FROM " . DB_TABLE_PREFIX . "t_audit_log WHERE s_actor_type = 'admin'");
$total_admin = $res_admin ? (int)$res_admin->row()['total'] : 0;

$res_customer = $comm->query("SELECT count(*) as total FROM " . DB_TABLE_PREFIX . "t_audit_log WHERE s_actor_type = 'customer'");
$total_customer = $res_customer ? (int)$res_customer->row()['total'] : 0;

$res_today = $comm->query("SELECT count(*) as total FROM " . DB_TABLE_PREFIX . "t_audit_log WHERE dt_created_at >= CURDATE()");
$total_today = $res_today ? (int)$res_today->row()['total'] : 0;

$res_ips = $comm->query("SELECT count(DISTINCT s_ip_address) as total FROM " . DB_TABLE_PREFIX . "t_audit_log");
$total_ips = $res_ips ? (int)$res_ips->row()['total'] : 0;

// Filtered Count
$res_filtered = $comm->query("SELECT count(*) as total FROM " . DB_TABLE_PREFIX . "t_audit_log WHERE $where_sql");
$filtered_total = $res_filtered ? (int)$res_filtered->row()['total'] : 0;
$total_pages = ceil($filtered_total / $per_page);

// Fetch Paginated Logs
$sql_logs = "SELECT * FROM " . DB_TABLE_PREFIX . "t_audit_log WHERE $where_sql ORDER BY pk_i_id DESC LIMIT $per_page OFFSET $offset";
$res_logs = $comm->query($sql_logs);
$logs = $res_logs ? $res_logs->result() : array();

$base_url = osc_admin_render_plugin_url('olx_audit_log/admin.php');
?>

<div class="audit-dashboard" style="padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="margin: 0; font-size: 26px; font-weight: 700; color: #1e293b;">
                <i class="fas fa-shield-alt" style="color: #6366f1; margin-right: 8px;"></i>Audit Trail & User Activity Logger
            </h1>
            <p style="margin: 6px 0 0 0; color: #64748b; font-size: 14px;">
                Complete real-time immutable audit history for customer and administrator actions with automated credential masking.
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="<?php echo $base_url . '&' . http_build_query(array_merge($_GET, array('export' => 'csv'))); ?>" class="btn btn-secondary" style="background: #10b981; color: #fff; font-weight: 600; padding: 10px 18px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <i class="fas fa-file-csv"></i> Export CSV Report
            </a>
        </div>
    </div>

    <!-- METRICS CARDS -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div style="background: #fff; padding: 18px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Total Logged Events</div>
            <div style="font-size: 28px; font-weight: 700; color: #1e293b; margin-top: 6px;"><?php echo number_format($total_logs, 0, ',', '.'); ?></div>
            <div style="font-size: 12px; color: #10b981; margin-top: 4px;"><i class="fas fa-check-circle"></i> Complete Ledger</div>
        </div>

        <div style="background: #fff; padding: 18px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Admin Actions</div>
            <div style="font-size: 28px; font-weight: 700; color: #6366f1; margin-top: 6px;"><?php echo number_format($total_admin, 0, ',', '.'); ?></div>
            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Back-office modifications</div>
        </div>

        <div style="background: #fff; padding: 18px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Customer Actions</div>
            <div style="font-size: 28px; font-weight: 700; color: #0284c7; margin-top: 6px;"><?php echo number_format($total_customer, 0, ',', '.'); ?></div>
            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Front-end user submissions</div>
        </div>

        <div style="background: #fff; padding: 18px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Actions Today</div>
            <div style="font-size: 28px; font-weight: 700; color: #10b981; margin-top: 6px;"><?php echo number_format($total_today, 0, ',', '.'); ?></div>
            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Recent 24h activity</div>
        </div>

        <div style="background: #fff; padding: 18px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Unique IPs Tracked</div>
            <div style="font-size: 28px; font-weight: 700; color: #d97706; margin-top: 6px;"><?php echo number_format($total_ips, 0, ',', '.'); ?></div>
            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Source addresses</div>
        </div>
    </div>

    <!-- FILTER FORM -->
    <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <form method="get" action="<?php echo osc_admin_base_url(true); ?>">
            <input type="hidden" name="page" value="plugins" />
            <input type="hidden" name="action" value="renderplugin" />
            <input type="hidden" name="file" value="olx_audit_log/admin.php" />

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Keyword Search</label>
                    <input type="text" name="sSearch" value="<?php echo osc_esc_html($sSearch); ?>" placeholder="User, IP, Action, or URL..." style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px;" />
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Actor Type</label>
                    <select name="sActorType" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px;">
                        <option value="all">All Actors</option>
                        <option value="admin" <?php if ($sActorType === 'admin') echo 'selected'; ?>>Administrator</option>
                        <option value="customer" <?php if ($sActorType === 'customer') echo 'selected'; ?>>Customer / User</option>
                        <option value="guest" <?php if ($sActorType === 'guest') echo 'selected'; ?>>Guest (Unauthenticated)</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Action Category</label>
                    <select name="sAction" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px;">
                        <option value="all">All Actions</option>
                        <option value="auth." <?php if ($sAction === 'auth.') echo 'selected'; ?>>Authentication (Logins/Logouts)</option>
                        <option value="listing." <?php if ($sAction === 'listing.') echo 'selected'; ?>>Listings (Create/Edit/Delete)</option>
                        <option value="user." <?php if ($sAction === 'user.') echo 'selected'; ?>>Users (Register/Profiles)</option>
                        <option value="admin." <?php if ($sAction === 'admin.') echo 'selected'; ?>>Admin Changes (Settings/Panels)</option>
                        <option value="comment." <?php if ($sAction === 'comment.') echo 'selected'; ?>>Comments</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Status</label>
                    <select name="sStatus" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px;">
                        <option value="all">All Statuses</option>
                        <option value="success" <?php if ($sStatus === 'success') echo 'selected'; ?>>Success</option>
                        <option value="failed" <?php if ($sStatus === 'failed') echo 'selected'; ?>>Failed</option>
                        <option value="denied" <?php if ($sStatus === 'denied') echo 'selected'; ?>>Denied</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">From Date</label>
                    <input type="date" name="dtFrom" value="<?php echo osc_esc_html($dtFrom); ?>" style="width: 100%; padding: 7px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px;" />
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">To Date</label>
                    <input type="date" name="dtTo" value="<?php echo osc_esc_html($dtTo); ?>" style="width: 100%; padding: 7px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px;" />
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="submit" style="flex: 1; background: #6366f1; color: #fff; border: none; padding: 9px 16px; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="<?php echo $base_url; ?>" style="background: #f1f5f9; color: #475569; text-decoration: none; padding: 9px 14px; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center;">
                        Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- LOGS DATA TABLE -->
    <div style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 15px; font-weight: 600; color: #1e293b;">
                Showing <?php echo number_format(min($filtered_total, $offset + 1), 0, ',', '.'); ?> - <?php echo number_format(min($filtered_total, $offset + $per_page), 0, ',', '.'); ?> of <?php echo number_format($filtered_total, 0, ',', '.'); ?> Entries
            </div>
            <?php if ($total_pages > 1): ?>
                <div style="display: flex; gap: 6px;">
                    <?php if ($page_num > 1): ?>
                        <a href="<?php echo $base_url . '&' . http_build_query(array_merge($_GET, array('iPage' => $page_num - 1))); ?>" style="padding: 5px 10px; border: 1px solid #cbd5e1; border-radius: 6px; text-decoration: none; font-size: 13px; color: #475569;">&laquo; Prev</a>
                    <?php endif; ?>
                    <span style="padding: 5px 12px; font-size: 13px; font-weight: 600; color: #1e293b;">Page <?php echo $page_num; ?> / <?php echo $total_pages; ?></span>
                    <?php if ($page_num < $total_pages): ?>
                        <a href="<?php echo $base_url . '&' . http_build_query(array_merge($_GET, array('iPage' => $page_num + 1))); ?>" style="padding: 5px 10px; border: 1px solid #cbd5e1; border-radius: 6px; text-decoration: none; font-size: 13px; color: #475569;">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13.5px; text-align: left;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <th style="padding: 12px 16px;">ID</th>
                        <th style="padding: 12px 16px;">Timestamp</th>
                        <th style="padding: 12px 16px;">Actor</th>
                        <th style="padding: 12px 16px;">Action</th>
                        <th style="padding: 12px 16px;">Target</th>
                        <th style="padding: 12px 16px;">Method & URL</th>
                        <th style="padding: 12px 16px;">IP Address</th>
                        <th style="padding: 12px 16px;">Status</th>
                        <th style="padding: 12px 16px; text-align: center;">Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="9" style="padding: 32px; text-align: center; color: #94a3b8; font-size: 15px;">
                                <i class="fas fa-folder-open" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                                No audit records matched your query filters.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <?php
                            $actor_badge_style = 'background: #f1f5f9; color: #64748b;';
                            if ($log['s_actor_type'] === 'admin') {
                                $actor_badge_style = 'background: #ede9fe; color: #6d28d9;';
                            } elseif ($log['s_actor_type'] === 'customer') {
                                $actor_badge_style = 'background: #e0f2fe; color: #0369a1;';
                            }

                            $status_badge_style = 'background: #dcfce7; color: #15803d;';
                            if ($log['s_status'] === 'failed') {
                                $status_badge_style = 'background: #fee2e2; color: #b91c1c;';
                            } elseif ($log['s_status'] === 'denied') {
                                $status_badge_style = 'background: #fef3c7; color: #b45309;';
                            }
                            ?>
                            <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.1s ease;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                                <td style="padding: 12px 16px; font-weight: 600; color: #64748b;">#<?php echo $log['pk_i_id']; ?></td>
                                <td style="padding: 12px 16px; white-space: nowrap; color: #334155;">
                                    <?php echo date('d/m/Y H:i:s', strtotime($log['dt_created_at'])); ?>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <span style="display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; <?php echo $actor_badge_style; ?>">
                                        <?php echo $log['s_actor_type']; ?>
                                    </span>
                                    <div style="font-weight: 600; color: #1e293b; margin-top: 3px;">
                                        <?php echo osc_esc_html($log['s_actor_name'] ? $log['s_actor_name'] : ($log['s_actor_email'] ? $log['s_actor_email'] : 'Guest')); ?>
                                    </div>
                                    <?php if ($log['s_actor_email'] && $log['s_actor_name']): ?>
                                        <div style="font-size: 11.5px; color: #64748b;"><?php echo osc_esc_html($log['s_actor_email']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <span style="font-family: monospace; font-size: 12px; font-weight: 700; background: #f1f5f9; padding: 3px 6px; border-radius: 4px; color: #334155;">
                                        <?php echo osc_esc_html($log['s_action']); ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 16px; color: #64748b;">
                                    <?php if ($log['s_target_type']): ?>
                                        <span style="font-weight: 500; color: #334155;"><?php echo osc_esc_html($log['s_target_type']); ?></span>
                                        <?php if ($log['s_target_id']): ?>
                                            <span style="font-size: 12px; color: #64748b;">#<?php echo osc_esc_html($log['s_target_id']); ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1;">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <span style="font-weight: 700; font-size: 11px; padding: 2px 6px; border-radius: 4px; background: <?php echo ($log['s_http_method'] === 'POST') ? '#dbeafe; color: #1d4ed8;' : '#f1f5f9; color: #475569;'; ?>">
                                        <?php echo $log['s_http_method']; ?>
                                    </span>
                                    <span style="font-size: 12px; color: #64748b; margin-left: 4px; font-family: monospace;" title="<?php echo osc_esc_html($log['s_url']); ?>">
                                        <?php echo osc_esc_html(strlen($log['s_url']) > 32 ? substr($log['s_url'], 0, 32) . '...' : $log['s_url']); ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 16px; font-family: monospace; font-size: 12px; color: #475569;">
                                    <?php echo osc_esc_html($log['s_ip_address']); ?>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <span style="display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; <?php echo $status_badge_style; ?>">
                                        <?php echo $log['s_status']; ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <button type="button" onclick="showAuditModal(<?php echo htmlspecialchars(json_encode($log), ENT_QUOTES, 'UTF-8'); ?>)" style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 5px 10px; border-radius: 6px; cursor: pointer; font-size: 12px; color: #475569; font-weight: 600;">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- AUDIT DETAIL MODAL -->
<div id="auditModal" style="display: none; position: fixed; z-index: 99999; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(2px); align-items: center; justify-content: center;">
    <div style="background: #fff; border-radius: 16px; width: 90%; max-width: 720px; max-height: 85vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); border: 1px solid #e2e8f0; padding: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; margin-bottom: 18px;">
            <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #1e293b;" id="modalTitle">Audit Record Details</h3>
            <button type="button" onclick="closeAuditModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #94a3b8;">&times;</button>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 18px; font-size: 13.5px;" id="modalMetadata">
            <!-- Populated via JS -->
        </div>

        <div style="margin-top: 14px;">
            <label style="display: block; font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 6px;">Submitted Input Payload (Masked / Sanitized):</label>
            <pre id="modalPayload" style="background: #0f172a; color: #38bdf8; padding: 14px; border-radius: 8px; font-size: 12.5px; font-family: monospace; overflow-x: auto; max-height: 280px;"></pre>
        </div>

        <div style="margin-top: 20px; text-align: right;">
            <button type="button" onclick="closeAuditModal()" style="background: #6366f1; color: #fff; border: none; padding: 9px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">Close</button>
        </div>
    </div>
</div>

<script>
function showAuditModal(log) {
    document.getElementById('modalTitle').textContent = 'Audit Event #' + log.pk_i_id + ': ' + log.s_action;
    
    let metaHtml = `
        <div><strong style="color: #64748b;">Timestamp:</strong> <div>${log.dt_created_at}</div></div>
        <div><strong style="color: #64748b;">Actor:</strong> <div>${log.s_actor_name || 'Guest'} (${log.s_actor_type.toUpperCase()})</div></div>
        <div><strong style="color: #64748b;">Email:</strong> <div>${log.s_actor_email || 'N/A'}</div></div>
        <div><strong style="color: #64748b;">Actor ID:</strong> <div>${log.fk_i_actor_id || 'N/A'}</div></div>
        <div><strong style="color: #64748b;">Client IP:</strong> <div>${log.s_ip_address}</div></div>
        <div><strong style="color: #64748b;">HTTP Method:</strong> <div>${log.s_http_method}</div></div>
        <div style="grid-column: 1 / -1;"><strong style="color: #64748b;">Request URL:</strong> <div style="font-family: monospace; word-break: break-all;">${log.s_url}</div></div>
        <div style="grid-column: 1 / -1;"><strong style="color: #64748b;">User-Agent:</strong> <div style="font-size: 12px; color: #64748b;">${log.s_user_agent || 'N/A'}</div></div>
    `;
    document.getElementById('modalMetadata').innerHTML = metaHtml;

    let payloadFormatted = 'No payload recorded';
    if (log.j_payload) {
        try {
            const parsed = JSON.parse(log.j_payload);
            payloadFormatted = JSON.stringify(parsed, null, 2);
        } catch(e) {
            payloadFormatted = log.j_payload;
        }
    }
    document.getElementById('modalPayload').textContent = payloadFormatted;
    document.getElementById('auditModal').style.display = 'flex';
}

function closeAuditModal() {
    document.getElementById('auditModal').style.display = 'none';
}

window.addEventListener('click', function(e) {
    const modal = document.getElementById('auditModal');
    if (e.target === modal) {
        modal.style.display = 'none';
    }
});
</script>
