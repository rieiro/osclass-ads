<?php
if (!defined('OC_ADMIN') || OC_ADMIN !== true) exit('Access denied');
if (!osc_is_admin_user_logged_in()) exit('Access denied');

$conn = DBConnectionClass::newInstance();
$db   = $conn->getOsclassDb();
$comm = new DBCommandClass($db);

// Base Admin URL for this page
$base_page_url = osc_admin_render_plugin_url('olx_smart_search/admin_queries.php');

// Stored system default for max result set
$saved_default_cap = (int)getPreference('latest_searches_max_result_set');
if ($saved_default_cap <= 0) {
    $saved_default_cap = 1000; // User required default of 1000
}

// Subaction Handling (Delete term, Save default cap, Purge)
$subaction = trim((string)Params::getParam('subaction'));

if ($subaction === 'save_default_cap') {
    osc_csrf_check();
    $new_cap = (int)Params::getParam('new_default_cap');
    if ($new_cap >= 50 && $new_cap <= 50000) {
        osc_set_preference('latest_searches_max_result_set', (string)$new_cap);
        osc_add_flash_ok_message(sprintf(__('Default maximum result set pool updated to %s queries.', 'olx_smart_search'), number_format($new_cap)), 'admin');
    } else {
        osc_add_flash_error_message(__('Invalid maximum result set value. Must be between 50 and 50,000.', 'olx_smart_search'), 'admin');
    }
    ob_end_clean();
    header('Location: ' . $base_page_url);
    exit;
}

if ($subaction === 'delete_term') {
    osc_csrf_check();
    $term_to_del = trim((string)Params::getParam('term'));
    if ($term_to_del !== '') {
        $del_sql = sprintf("DELETE FROM %st_latest_searches WHERE s_search = %s", DB_TABLE_PREFIX, $comm->escape($term_to_del));
        $comm->query($del_sql);
        osc_add_flash_ok_message(sprintf(__('Search term "%s" has been removed from log.', 'olx_smart_search'), osc_esc_html($term_to_del)), 'admin');
    }
    ob_end_clean();
    header('Location: ' . $base_page_url . '&mode=' . urlencode(Params::getParam('mode')) . '&max_pool=' . (int)Params::getParam('max_pool'));
    exit;
}

if ($subaction === 'purge_all') {
    osc_csrf_check();
    $purge_sql = sprintf("DELETE FROM %st_latest_searches", DB_TABLE_PREFIX);
    $comm->query($purge_sql);
    osc_add_flash_ok_message(__('All search query logs have been purged.', 'olx_smart_search'), 'admin');
    ob_end_clean();
    header('Location: ' . $base_page_url);
    exit;
}

// Parameters
$sTerm        = trim((string)Params::getParam('sTerm'));
$mode         = (Params::getParam('mode') === 'stream') ? 'stream' : 'aggregated';
$raw_max_pool = Params::getParam('max_pool');
$max_pool     = ($raw_max_pool !== '' && $raw_max_pool !== null) ? max(50, min(50000, (int)$raw_max_pool)) : $saved_default_cap;
$per_page     = max(10, min(200, (int)Params::getParam('iPerPage', false, false, 25)));
if ($per_page <= 0) $per_page = 25;
$page_num     = max(1, (int)Params::getParam('iPage'));
$offset       = ($page_num - 1) * $per_page;
$sort         = trim((string)Params::getParam('sort'));
if ($sort === '') {
    $sort = ($mode === 'stream') ? 'date_desc' : 'count_desc';
}

// Determine SQL Order By
switch ($sort) {
    case 'count_asc':
        $order_by = 'i_total ASC, last_date DESC';
        break;
    case 'date_desc':
        $order_by = ($mode === 'stream') ? 'd_date DESC' : 'last_date DESC';
        break;
    case 'date_asc':
        $order_by = ($mode === 'stream') ? 'd_date ASC' : 'first_date ASC';
        break;
    case 'alpha_asc':
        $order_by = 's_search ASC';
        break;
    case 'alpha_desc':
        $order_by = 's_search DESC';
        break;
    case 'count_desc':
    default:
        $order_by = ($mode === 'stream') ? 'd_date DESC' : 'i_total DESC, last_date DESC';
        break;
}

// -------------------------------------------------------------
// HIGH PERFORMANCE OPTIMIZED POOL QUERY
// Respects maximum result set (default 1000, customizable)
// -------------------------------------------------------------
$where_sub = " ";
if ($sTerm !== '') {
    $escTerm = $comm->escape('%' . $sTerm . '%');
    $where_sub = " WHERE s_search LIKE $escTerm ";
}

$pool_sql = sprintf(
    "SELECT s_search, d_date FROM %st_latest_searches%sORDER BY d_date DESC LIMIT %d",
    DB_TABLE_PREFIX,
    $where_sub,
    $max_pool
);

// CSV EXPORT STREAM
if (Params::getParam('export') === 'csv') {
    ob_end_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="search_queries_' . $mode . '_' . date('Y-m-d_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

    if ($mode === 'aggregated') {
        fputcsv($out, array('Rank', 'Search Term', 'Searches Count', 'First Searched', 'Last Searched'));
        $csv_sql = "SELECT s_search, COUNT(*) as i_total, MIN(d_date) as first_date, MAX(d_date) as last_date "
                 . "FROM ($pool_sql) as p GROUP BY s_search ORDER BY $order_by";
        $res = $comm->query($csv_sql);
        if ($res) {
            $rows = $res->result();
            $rank = 1;
            foreach ($rows as $row) {
                fputcsv($out, array($rank++, $row['s_search'], $row['i_total'], $row['first_date'], $row['last_date']));
            }
        }
    } else {
        fputcsv($out, array('Timestamp', 'Search Term'));
        $csv_sql = "SELECT d_date, s_search FROM ($pool_sql) as p ORDER BY $order_by";
        $res = $comm->query($csv_sql);
        if ($res) {
            $rows = $res->result();
            foreach ($rows as $row) {
                fputcsv($out, array($row['d_date'], $row['s_search']));
            }
        }
    }
    fclose($out);
    exit;
}

// Calculate Total in Filtered Pool
$t_start = microtime(true);

if ($mode === 'aggregated') {
    $count_res = $comm->query("SELECT COUNT(DISTINCT s_search) as total_distinct, COUNT(*) as total_rows FROM ($pool_sql) as p");
    $count_data = $count_res ? $count_res->row() : array('total_distinct' => 0, 'total_rows' => 0);
    $total_records = (int)$count_data['total_distinct'];
    $total_queries_in_pool = (int)$count_data['total_rows'];

    $data_sql = "SELECT s_search, COUNT(*) as i_total, MIN(d_date) as first_date, MAX(d_date) as last_date "
              . "FROM ($pool_sql) as p "
              . "GROUP BY s_search "
              . "ORDER BY $order_by "
              . "LIMIT $offset, $per_page";
    $data_res = $comm->query($data_sql);
    $items = $data_res ? $data_res->result() : array();
} else {
    $count_res = $comm->query("SELECT COUNT(*) as total_rows FROM ($pool_sql) as p");
    $count_data = $count_res ? $count_res->row() : array('total_rows' => 0);
    $total_records = (int)$count_data['total_rows'];
    $total_queries_in_pool = $total_records;

    $data_sql = "SELECT s_search, d_date FROM ($pool_sql) as p ORDER BY $order_by LIMIT $offset, $per_page";
    $data_res = $comm->query($data_sql);
    $items = $data_res ? $data_res->result() : array();
}

$query_duration_ms = round((microtime(true) - $t_start) * 1000, 2);
$total_pages = max(1, (int)ceil($total_records / $per_page));

// Global Statistics for KPI Cards
$stat_total_all = LatestSearches::newInstance()->countAllSearches();
$top_item = null;
if (!empty($items) && $mode === 'aggregated' && ($sort === 'count_desc' || $sort === '')) {
    $top_item = $items[0];
} else {
    $top_res = $comm->query("SELECT s_search, COUNT(*) as i_total FROM ($pool_sql) as p GROUP BY s_search ORDER BY i_total DESC LIMIT 1");
    if ($top_res) {
        $top_item = $top_res->row();
    }
}
$top_count = (!empty($top_item) && isset($top_item['i_total'])) ? (int)$top_item['i_total'] : 1;

// Render Admin Theme Header
osc_current_admin_theme_path('parts/header.php');
?>
<style>
.sq-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.sq-kpi-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 16px 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    display: flex;
    flex-direction: column;
}
.sq-kpi-label {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 6px;
}
.sq-kpi-val {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
}
.sq-kpi-sub {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 4px;
}
.sq-toolbar {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 16px 20px;
    margin-bottom: 20px;
}
.sq-filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: flex-end;
}
.sq-filter-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.sq-filter-group label {
    font-size: 11px;
    font-weight: 600;
    color: #475569;
    text-transform: uppercase;
}
.sq-filter-group input, .sq-filter-group select {
    height: 36px;
    padding: 6px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 13px;
    background-color: #fff;
}
.sq-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
    background: #e0f2fe;
    color: #0369a1;
}
.sq-bar-wrap {
    width: 90px;
    height: 6px;
    background: #f1f5f9;
    border-radius: 3px;
    overflow: hidden;
    display: inline-block;
    vertical-align: middle;
    margin-left: 8px;
}
.sq-bar-fill {
    height: 100%;
    background: #0ea5e9;
    border-radius: 3px;
}
.sq-table-container {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.sq-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    text-align: left;
}
.sq-table th {
    background: #f8fafc;
    color: #475569;
    font-weight: 600;
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.sq-table td {
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
    vertical-align: middle;
}
.sq-table tr:hover td {
    background-color: #f8fafc;
}
.sq-term-badge {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    background: #f1f5f9;
    padding: 3px 8px;
    border-radius: 4px;
    border: 1px solid #e2e8f0;
}
.sq-actions a {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    text-decoration: none;
    font-weight: 500;
}
.sq-btn-view {
    color: #0284c7;
    background: #f0f9ff;
    border: 1px solid #bae6fd;
}
.sq-btn-view:hover {
    background: #e0f2fe;
}
.sq-btn-del {
    color: #dc2626;
    background: #fef2f2;
    border: 1px solid #fecaca;
    margin-left: 4px;
}
.sq-btn-del:hover {
    background: #fee2e2;
}
.sq-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 20px;
    background: #fafafa;
    border-top: 1px solid #e2e8f0;
    font-size: 13px;
    color: #64748b;
}
.sq-page-links {
    display: flex;
    gap: 4px;
}
.sq-page-links a, .sq-page-links span {
    padding: 6px 12px;
    border-radius: 4px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #334155;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
}
.sq-page-links span.active {
    background: #0284c7;
    border-color: #0284c7;
    color: #ffffff;
}
.sq-page-links a:hover {
    background: #f1f5f9;
}
.sq-mode-tabs {
    display: inline-flex;
    background: #f1f5f9;
    padding: 3px;
    border-radius: 6px;
    margin-right: 12px;
}
.sq-mode-tab {
    padding: 6px 14px;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-decoration: none;
    border-radius: 4px;
}
.sq-mode-tab.active {
    background: #ffffff;
    color: #0f172a;
    box-shadow: 0 1px 2px rgba(0,0,0,0.08);
}
</style>

<div style="padding: 10px 0 30px 0;">
    <!-- Top Header Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <div>
            <h2 class="render-title" style="margin: 0; font-size: 22px; font-weight: 700; color: #0f172a;">
                <?php _e('Search Queries Log & Analytics', 'olx_smart_search'); ?>
            </h2>
            <p style="margin: 4px 0 0 0; color: #64748b; font-size: 13px;">
                <?php _e('Inspect, monitor, and audit live user search queries with customizable result sets and instant pagination.', 'olx_smart_search'); ?>
            </p>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <a href="<?php echo $base_page_url . '&mode=' . $mode . '&sTerm=' . urlencode($sTerm) . '&max_pool=' . $max_pool . '&sort=' . $sort . '&export=csv'; ?>" class="btn" style="background: #ffffff; border: 1px solid #cbd5e1; color: #334155; font-weight: 600;">
                <i class="fa fa-download"></i> <?php _e('Export CSV', 'olx_smart_search'); ?>
            </a>
            <a href="<?php echo osc_admin_base_url(true) . '?page=settings&action=latest_searches'; ?>" class="btn" style="background: #ffffff; border: 1px solid #cbd5e1; color: #334155; font-weight: 600;">
                <i class="fa fa-cog"></i> <?php _e('Purge / Retention Settings', 'olx_smart_search'); ?>
            </a>
            <a href="<?php echo $base_page_url . '&subaction=purge_all&' . osc_csrf_token_url(); ?>" class="btn" onclick="return confirm('<?php echo osc_esc_js(__('Are you sure you want to purge all stored search queries?', 'olx_smart_search')); ?>');" style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; font-weight: 600;">
                <i class="fa fa-trash"></i> <?php _e('Truncate Log', 'olx_smart_search'); ?>
            </a>
        </div>
    </div>

    <!-- KPI Summary Metrics -->
    <div class="sq-kpi-grid">
        <div class="sq-kpi-card">
            <span class="sq-kpi-label"><?php _e('Total Queries in DB', 'olx_smart_search'); ?></span>
            <span class="sq-kpi-val"><?php echo number_format($stat_total_all); ?></span>
            <span class="sq-kpi-sub"><?php _e('Total searches ever recorded', 'olx_smart_search'); ?></span>
        </div>
        <div class="sq-kpi-card">
            <span class="sq-kpi-label"><?php _e('Queries in Current Pool', 'olx_smart_search'); ?></span>
            <span class="sq-kpi-val" style="color: #0284c7;"><?php echo number_format($total_queries_in_pool); ?></span>
            <span class="sq-kpi-sub"><?php echo sprintf(__('Capped at max pool: %s', 'olx_smart_search'), number_format($max_pool)); ?></span>
        </div>
        <div class="sq-kpi-card">
            <span class="sq-kpi-label"><?php _e('Unique Search Terms', 'olx_smart_search'); ?></span>
            <span class="sq-kpi-val" style="color: #16a34a;"><?php echo number_format($mode === 'aggregated' ? $total_records : count(array_unique(array_column($items, 's_search')))); ?></span>
            <span class="sq-kpi-sub"><?php _e('Distinct user queries analyzed', 'olx_smart_search'); ?></span>
        </div>
        <div class="sq-kpi-card">
            <span class="sq-kpi-label"><?php _e('Most Searched Term', 'olx_smart_search'); ?></span>
            <span class="sq-kpi-val" style="font-size: 18px; color: #e11d48; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                <?php echo !empty($top_item['s_search']) ? osc_esc_html($top_item['s_search']) : '—'; ?>
                <?php if (!empty($top_item['i_total'])): ?>
                    <span class="sq-badge" style="background:#ffe4e6; color:#e11d48; font-size:11px;"><?php echo $top_item['i_total']; ?> hits</span>
                <?php endif; ?>
            </span>
            <span class="sq-kpi-sub"><?php echo sprintf(__('Indexed query time: %s ms', 'olx_smart_search'), $query_duration_ms); ?></span>
        </div>
    </div>

    <!-- Filter & Customization Toolbar -->
    <div class="sq-toolbar">
        <form method="get" action="<?php echo osc_admin_base_url(true); ?>" class="sq-filter-form">
            <input type="hidden" name="page" value="plugins" />
            <input type="hidden" name="action" value="renderplugin" />
            <input type="hidden" name="file" value="olx_smart_search/admin_queries.php" />

            <!-- Mode Switcher -->
            <div class="sq-filter-group">
                <label><?php _e('View Mode', 'olx_smart_search'); ?></label>
                <div class="sq-mode-tabs">
                    <a href="<?php echo $base_page_url . '&mode=aggregated&sTerm=' . urlencode($sTerm) . '&max_pool=' . $max_pool . '&iPerPage=' . $per_page; ?>" class="sq-mode-tab <?php echo ($mode === 'aggregated') ? 'active' : ''; ?>">
                        <i class="fa fa-chart-pie"></i> <?php _e('Top Trends', 'olx_smart_search'); ?>
                    </a>
                    <a href="<?php echo $base_page_url . '&mode=stream&sTerm=' . urlencode($sTerm) . '&max_pool=' . $max_pool . '&iPerPage=' . $per_page; ?>" class="sq-mode-tab <?php echo ($mode === 'stream') ? 'active' : ''; ?>">
                        <i class="fa fa-stream"></i> <?php _e('Live Stream', 'olx_smart_search'); ?>
                    </a>
                </div>
            </div>

            <!-- Search Term Filter -->
            <div class="sq-filter-group" style="flex: 1; min-width: 180px;">
                <label><?php _e('Filter Search Term', 'olx_smart_search'); ?></label>
                <input type="text" name="sTerm" value="<?php echo osc_esc_html($sTerm); ?>" placeholder="<?php echo osc_esc_html(__('e.g. carro, imovel, corolla...', 'olx_smart_search')); ?>" />
            </div>

            <!-- Maximum Result Set Pool (Default 1000, Customizable) -->
            <div class="sq-filter-group" style="width: 170px;">
                <label title="<?php echo osc_esc_html(__('Maximum number of latest queries scanned into result set pool. Default is 1000.', 'olx_smart_search')); ?>">
                    <?php _e('Max Result Set', 'olx_smart_search'); ?> <i class="fa fa-info-circle" style="color:#94a3b8;"></i>
                </label>
                <select name="max_pool" onchange="this.form.submit();">
                    <option value="500" <?php echo ($max_pool == 500) ? 'selected' : ''; ?>>500 <?php _e('queries', 'olx_smart_search'); ?></option>
                    <option value="1000" <?php echo ($max_pool == 1000) ? 'selected' : ''; ?>>1,000 (<?php _e('Default', 'olx_smart_search'); ?>)</option>
                    <option value="2500" <?php echo ($max_pool == 2500) ? 'selected' : ''; ?>>2,500 <?php _e('queries', 'olx_smart_search'); ?></option>
                    <option value="5000" <?php echo ($max_pool == 5000) ? 'selected' : ''; ?>>5,000 <?php _e('queries', 'olx_smart_search'); ?></option>
                    <option value="10000" <?php echo ($max_pool == 10000) ? 'selected' : ''; ?>>10,000 <?php _e('queries', 'olx_smart_search'); ?></option>
                    <?php if (!in_array($max_pool, array(500, 1000, 2500, 5000, 10000))): ?>
                        <option value="<?php echo (int)$max_pool; ?>" selected><?php echo number_format($max_pool); ?> (<?php _e('Custom', 'olx_smart_search'); ?>)</option>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Items Per Page -->
            <div class="sq-filter-group" style="width: 120px;">
                <label><?php _e('Per Page', 'olx_smart_search'); ?></label>
                <select name="iPerPage" onchange="this.form.submit();">
                    <option value="20" <?php echo ($per_page == 20) ? 'selected' : ''; ?>>20</option>
                    <option value="25" <?php echo ($per_page == 25) ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?php echo ($per_page == 50) ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo ($per_page == 100) ? 'selected' : ''; ?>>100</option>
                </select>
            </div>

            <!-- Sorting -->
            <div class="sq-filter-group" style="width: 170px;">
                <label><?php _e('Sort By', 'olx_smart_search'); ?></label>
                <select name="sort" onchange="this.form.submit();">
                    <?php if ($mode === 'aggregated'): ?>
                        <option value="count_desc" <?php echo ($sort === 'count_desc') ? 'selected' : ''; ?>><?php _e('Most Frequent (Hits)', 'olx_smart_search'); ?></option>
                        <option value="count_asc" <?php echo ($sort === 'count_asc') ? 'selected' : ''; ?>><?php _e('Least Frequent', 'olx_smart_search'); ?></option>
                        <option value="date_desc" <?php echo ($sort === 'date_desc') ? 'selected' : ''; ?>><?php _e('Most Recent', 'olx_smart_search'); ?></option>
                        <option value="date_asc" <?php echo ($sort === 'date_asc') ? 'selected' : ''; ?>><?php _e('Oldest First', 'olx_smart_search'); ?></option>
                        <option value="alpha_asc" <?php echo ($sort === 'alpha_asc') ? 'selected' : ''; ?>><?php _e('Alphabetical (A-Z)', 'olx_smart_search'); ?></option>
                    <?php else: ?>
                        <option value="date_desc" <?php echo ($sort === 'date_desc') ? 'selected' : ''; ?>><?php _e('Newest First', 'olx_smart_search'); ?></option>
                        <option value="date_asc" <?php echo ($sort === 'date_asc') ? 'selected' : ''; ?>><?php _e('Oldest First', 'olx_smart_search'); ?></option>
                        <option value="alpha_asc" <?php echo ($sort === 'alpha_asc') ? 'selected' : ''; ?>><?php _e('Search Term (A-Z)', 'olx_smart_search'); ?></option>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Submit & Reset -->
            <div class="sq-filter-group" style="flex-direction: row; gap: 6px;">
                <button type="submit" class="btn btn-submit" style="background:#0284c7; color:#fff; height:36px; padding: 0 16px;">
                    <i class="fa fa-filter"></i> <?php _e('Filter', 'olx_smart_search'); ?>
                </button>
                <?php if ($sTerm !== '' || $max_pool != $saved_default_cap || $sort !== ($mode === 'stream' ? 'date_desc' : 'count_desc')): ?>
                    <a href="<?php echo $base_page_url . '&mode=' . $mode; ?>" class="btn" style="height:36px; line-height:24px; background:#f1f5f9; color:#475569;">
                        <?php _e('Reset', 'olx_smart_search'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Save current max_pool as persistent default if changed -->
        <?php if ($max_pool != $saved_default_cap): ?>
            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed #e2e8f0; font-size: 12px; color: #64748b; display: flex; align-items: center; justify-content: space-between;">
                <span>
                    <?php echo sprintf(__('Current pool size is <strong>%s</strong> (System saved default is %s).', 'olx_smart_search'), number_format($max_pool), number_format($saved_default_cap)); ?>
                </span>
                <a href="<?php echo $base_page_url . '&subaction=save_default_cap&new_default_cap=' . $max_pool . '&' . osc_csrf_token_url(); ?>" class="btn" style="font-size: 11px; padding: 2px 8px; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">
                    <i class="fa fa-save"></i> <?php echo sprintf(__('Set %s as default result set cap', 'olx_smart_search'), number_format($max_pool)); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Results Data Table -->
    <div class="sq-table-container">
        <table class="sq-table">
            <thead>
                <tr>
                    <?php if ($mode === 'aggregated'): ?>
                        <th style="width: 50px; text-align: center;">#</th>
                        <th><?php _e('Search Term', 'olx_smart_search'); ?></th>
                        <th style="width: 190px;"><?php _e('Frequency (Hits)', 'olx_smart_search'); ?></th>
                        <th style="width: 170px;"><?php _e('First Recorded', 'olx_smart_search'); ?></th>
                        <th style="width: 170px;"><?php _e('Last Recorded', 'olx_smart_search'); ?></th>
                        <th style="width: 160px; text-align: right;"><?php _e('Actions', 'olx_smart_search'); ?></th>
                    <?php else: ?>
                        <th style="width: 60px; text-align: center;">#</th>
                        <th style="width: 220px;"><?php _e('Timestamp', 'olx_smart_search'); ?></th>
                        <th><?php _e('Search Term', 'olx_smart_search'); ?></th>
                        <th style="width: 160px; text-align: right;"><?php _e('Actions', 'olx_smart_search'); ?></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="<?php echo ($mode === 'aggregated') ? '6' : '4'; ?>" style="text-align: center; padding: 48px; color: #94a3b8;">
                            <i class="fa fa-search" style="font-size: 32px; margin-bottom: 12px; display: block; opacity: 0.5;"></i>
                            <strong><?php _e('No search queries found matching your criteria.', 'olx_smart_search'); ?></strong>
                            <p style="margin: 6px 0 0 0; font-size: 12px;">
                                <?php if (!osc_save_latest_searches()): ?>
                                    <span style="color: #dc2626; font-weight: 600;"><?php _e('Notice: Latest searches logging is currently disabled in Settings.', 'olx_smart_search'); ?></span>
                                    <br><a href="<?php echo osc_admin_base_url(true) . '?page=settings&action=latest_searches'; ?>" style="color: #0284c7; text-decoration: underline;"><?php _e('Enable logging in Latest Searches Settings &rarr;', 'olx_smart_search'); ?></a>
                                <?php else: ?>
                                    <?php _e('User searches will appear here automatically as visitors search on your website.', 'olx_smart_search'); ?>
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $row_idx = $offset + 1;
                    foreach ($items as $item): 
                        $pct = ($top_count > 0 && isset($item['i_total'])) ? min(100, round(($item['i_total'] / $top_count) * 100)) : 0;
                        $frontend_search_url = osc_base_url(true) . '?page=search&sPattern=' . urlencode($item['s_search']);
                        $filter_timeline_url = $base_page_url . '&mode=stream&sTerm=' . urlencode($item['s_search']) . '&max_pool=' . $max_pool;
                        $delete_url = $base_page_url . '&subaction=delete_term&term=' . urlencode($item['s_search']) . '&mode=' . $mode . '&max_pool=' . $max_pool . '&' . osc_csrf_token_url();
                    ?>
                        <tr>
                            <?php if ($mode === 'aggregated'): ?>
                                <td style="text-align: center; color: #94a3b8; font-weight: 600;"><?php echo $row_idx++; ?></td>
                                <td>
                                    <span class="sq-term-badge">
                                        <i class="fa fa-search" style="color:#94a3b8; font-size:11px; margin-right:4px;"></i>
                                        <?php echo osc_esc_html($item['s_search']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="sq-badge"><?php echo number_format($item['i_total']); ?> <?php echo ($item['i_total'] == 1) ? __('search', 'olx_smart_search') : __('searches', 'olx_smart_search'); ?></span>
                                    <div class="sq-bar-wrap" title="<?php echo $pct; ?>% <?php _e('of top query volume', 'olx_smart_search'); ?>">
                                        <div class="sq-bar-fill" style="width: <?php echo max(6, $pct); ?>%;"></div>
                                    </div>
                                </td>
                                <td style="color: #64748b; font-size: 12px;">
                                    <?php echo date('M d, Y H:i', strtotime($item['first_date'])); ?>
                                </td>
                                <td style="color: #0f172a; font-size: 12px; font-weight: 500;">
                                    <?php echo date('M d, Y H:i', strtotime($item['last_date'])); ?>
                                </td>
                                <td class="sq-actions" style="text-align: right;">
                                    <a href="<?php echo $frontend_search_url; ?>" target="_blank" class="sq-btn-view" title="<?php _e('View search results on website', 'olx_smart_search'); ?>">
                                        <i class="fa fa-external-link-alt"></i> <?php _e('Test', 'olx_smart_search'); ?>
                                    </a>
                                    <a href="<?php echo $filter_timeline_url; ?>" class="sq-btn-view" style="color:#6366f1; background:#eef2ff; border-color:#c7d2fe;" title="<?php _e('View timeline of this query', 'olx_smart_search'); ?>">
                                        <i class="fa fa-history"></i>
                                    </a>
                                    <a href="<?php echo $delete_url; ?>" class="sq-btn-del" onclick="return confirm('<?php echo osc_esc_js(sprintf(__('Delete all logged instances of "%s"?', 'olx_smart_search'), $item['s_search'])); ?>');" title="<?php _e('Delete term from log', 'olx_smart_search'); ?>">
                                        <i class="fa fa-times"></i>
                                    </a>
                                </td>
                            <?php else: ?>
                                <td style="text-align: center; color: #94a3b8; font-weight: 600;"><?php echo $row_idx++; ?></td>
                                <td style="color: #0f172a; font-size: 12px; font-family: ui-monospace, monospace;">
                                    <i class="far fa-clock" style="color:#94a3b8; margin-right:4px;"></i>
                                    <?php echo date('Y-m-d H:i:s', strtotime($item['d_date'])); ?>
                                </td>
                                <td>
                                    <span class="sq-term-badge">
                                        <i class="fa fa-search" style="color:#94a3b8; font-size:11px; margin-right:4px;"></i>
                                        <?php echo osc_esc_html($item['s_search']); ?>
                                    </span>
                                </td>
                                <td class="sq-actions" style="text-align: right;">
                                    <a href="<?php echo $frontend_search_url; ?>" target="_blank" class="sq-btn-view" title="<?php _e('View search results on website', 'olx_smart_search'); ?>">
                                        <i class="fa fa-external-link-alt"></i> <?php _e('Test', 'olx_smart_search'); ?>
                                    </a>
                                    <a href="<?php echo $delete_url; ?>" class="sq-btn-del" onclick="return confirm('<?php echo osc_esc_js(sprintf(__('Delete all logged instances of "%s"?', 'olx_smart_search'), $item['s_search'])); ?>');" title="<?php _e('Delete term from log', 'olx_smart_search'); ?>">
                                        <i class="fa fa-times"></i>
                                    </a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Interactive Pagination -->
        <?php if ($total_records > 0): 
            $start_item = $offset + 1;
            $end_item   = min($total_records, $offset + $per_page);
            $query_args = array(
                'page'     => 'plugins',
                'action'   => 'renderplugin',
                'file'     => 'olx_smart_search/admin_queries.php',
                'mode'     => $mode,
                'sTerm'    => $sTerm,
                'max_pool' => $max_pool,
                'iPerPage' => $per_page,
                'sort'     => $sort
            );
        ?>
            <div class="sq-pagination">
                <div>
                    <?php echo sprintf(
                        __('Showing <strong>%s–%s</strong> of <strong>%s</strong> %s (scanned from latest %s queries pool in %s ms)', 'olx_smart_search'),
                        number_format($start_item),
                        number_format($end_item),
                        number_format($total_records),
                        ($mode === 'aggregated') ? __('terms', 'olx_smart_search') : __('queries', 'olx_smart_search'),
                        number_format($total_queries_in_pool),
                        $query_duration_ms
                    ); ?>
                </div>
                <div class="sq-page-links">
                    <!-- Previous Page -->
                    <?php if ($page_num > 1): ?>
                        <a href="<?php echo osc_admin_base_url(true) . '?' . http_build_query(array_merge($query_args, array('iPage' => $page_num - 1))); ?>">&laquo; <?php _e('Prev', 'olx_smart_search'); ?></a>
                    <?php endif; ?>

                    <!-- Numbered Pages -->
                    <?php
                    $start_p = max(1, $page_num - 2);
                    $end_p   = min($total_pages, $page_num + 2);

                    if ($start_p > 1) {
                        echo '<a href="' . osc_admin_base_url(true) . '?' . http_build_query(array_merge($query_args, array('iPage' => 1))) . '">1</a>';
                        if ($start_p > 2) echo '<span>&hellip;</span>';
                    }

                    for ($p = $start_p; $p <= $end_p; $p++) {
                        if ($p == $page_num) {
                            echo '<span class="active">' . $p . '</span>';
                        } else {
                            echo '<a href="' . osc_admin_base_url(true) . '?' . http_build_query(array_merge($query_args, array('iPage' => $p))) . '">' . $p . '</a>';
                        }
                    }

                    if ($end_p < $total_pages) {
                        if ($end_p < $total_pages - 1) echo '<span>&hellip;</span>';
                        echo '<a href="' . osc_admin_base_url(true) . '?' . http_build_query(array_merge($query_args, array('iPage' => $total_pages))) . '">' . $total_pages . '</a>';
                    }
                    ?>

                    <!-- Next Page -->
                    <?php if ($page_num < $total_pages): ?>
                        <a href="<?php echo osc_admin_base_url(true) . '?' . http_build_query(array_merge($query_args, array('iPage' => $page_num + 1))); ?>"><?php _e('Next', 'olx_smart_search'); ?> &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
osc_current_admin_theme_path('parts/footer.php');
