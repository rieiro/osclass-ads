<?php
$slug = Params::getParam('store_slug');
$dao = new DAO();
$dao->udb = DBConnectionClass::newInstance()->getOsclassDb();
$dao->dao = new DBCommandClass($dao->udb);

$dao->dao->select();
$dao->dao->from(DB_TABLE_PREFIX . 't_olx_stores');
$dao->dao->where('s_slug', $slug);
$res = $dao->dao->get();

if (!$res || $res->numRows() == 0) {
    header('Location: ' . osc_base_url());
    exit;
}
$store = $res->row();
$seller_id = (int)$store['fk_i_user_id'];

// Fetch seller inventory
$dao->dao->select('it.*, id.s_title, id.s_description, loc.s_city, loc.s_region');
$dao->dao->from(DB_TABLE_PREFIX . 't_item it');
$dao->dao->join(DB_TABLE_PREFIX . 't_item_description id', 'it.pk_i_id = id.fk_i_item_id', 'LEFT');
$dao->dao->join(DB_TABLE_PREFIX . 't_item_location loc', 'it.pk_i_id = loc.fk_i_item_id', 'LEFT');
$dao->dao->where('it.fk_i_user_id', $seller_id);
$dao->dao->where('it.b_active', 1);
$dao->dao->where('it.b_enabled', 1);
$dao->dao->orderBy('it.dt_pub_date', 'DESC');
$dao->dao->limit(60);
$items_res = $dao->dao->get();
$items = ($items_res && $items_res->numRows() > 0) ? $items_res->result() : array();

// Single batch query for thumbnails to eliminate N+1 query bottleneck
$item_thumbs = array();
if (!empty($items)) {
    $item_ids = array_map('intval', array_column($items, 'pk_i_id'));
    $ids_in = implode(',', $item_ids);
    $res_query = $dao->dao->query("SELECT fk_i_item_id, pk_i_id, s_path, s_name, s_extension 
        FROM " . DB_TABLE_PREFIX . "t_item_resource 
        WHERE fk_i_item_id IN ({$ids_in}) 
        ORDER BY pk_i_id ASC");
    if ($res_query && $res_query->numRows() > 0) {
        $res_rows = $res_query->result();
        foreach ($res_rows as $r) {
            $iid = (int)$r['fk_i_item_id'];
            if (!isset($item_thumbs[$iid])) {
                $ext = !empty($r['s_extension']) ? $r['s_extension'] : 'webp';
                $path = !empty($r['s_path']) ? $r['s_path'] : 'oc-content/uploads/';
                $item_thumbs[$iid] = osc_base_url() . $path . $r['pk_i_id'] . '_thumbnail.' . $ext;
            }
        }
    }
}

osc_current_web_theme_path('header.php');
?>

<div class="wrapper" style="margin-top: 20px;">
    <!-- Store Header Banner -->
    <div style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); color: #fff; border-radius: 12px; padding: 35px 30px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
            <div>
                <span style="background: #38bdf8; color: #082f49; font-size: 11px; font-weight: 800; text-transform: uppercase; padding: 4px 8px; border-radius: 4px; letter-spacing: 0.5px;">Verified Pro Dealer</span>
                <h1 style="color: #fff; margin: 10px 0 5px 0; font-size: 28px;"><?php echo osc_esc_html($store['s_store_name']); ?></h1>
                <?php if (!empty($store['s_tagline'])): ?>
                    <p style="color: #cbd5e1; margin: 0 0 15px 0; font-size: 16px;"><?php echo osc_esc_html($store['s_tagline']); ?></p>
                <?php endif; ?>
                <?php if (!empty($store['s_address'])): ?>
                    <p style="color: #94a3b8; font-size: 13px; margin: 0; display: flex; align-items: center; gap: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        <?php echo osc_esc_html($store['s_address']); ?>
                    </p>
                <?php endif; ?>
            </div>

            <?php if (!empty($store['s_whatsapp'])): 
                $clean_wa = preg_replace('/[^0-9]/', '', $store['s_whatsapp']);
            ?>
                <a href="https://wa.me/55<?php echo $clean_wa; ?>" target="_blank" rel="noopener noreferrer" style="background: #22c55e; color: #fff; text-decoration: none; padding: 12px 20px; border-radius: 6px; font-weight: bold; font-size: 14px; display: inline-flex; align-items: center; gap: 8px;">
                    Contact on WhatsApp
                </a>
            <?php endif; ?>
        </div>

        <?php if (!empty($store['s_bio'])): ?>
            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.15); color: #e2e8f0; font-size: 14px; line-height: 1.6;">
                <?php echo nl2br(osc_esc_html($store['s_bio'])); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Inventory Section -->
    <div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0; font-size: 20px; color: #0f172a;">Available Inventory (<?php echo count($items); ?> Listings)</h2>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px;">
        <?php if (empty($items)): ?>
            <p style="color: #64748b;">No active listings found for this store right now.</p>
        <?php else: ?>
            <?php foreach ($items as $it): 
                $item_link = osc_item_url_ns($it['pk_i_id']);
                $thumb_url = $item_thumbs[$it['pk_i_id']] ?? (osc_current_web_theme_url('images/no_photo.gif'));
            ?>
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column;">
                    <a href="<?php echo osc_esc_html($item_link); ?>" style="display: block; height: 160px; background: #f8fafc; overflow: hidden;">
                        <img src="<?php echo osc_esc_html($thumb_url); ?>" alt="<?php echo osc_esc_html($it['s_title']); ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='<?php echo osc_current_web_theme_url('images/no_photo.gif'); ?>';"/>
                    </a>
                    <div style="padding: 15px; flex: 1; display: flex; flex-direction: column;">
                        <div style="font-size: 18px; font-weight: 800; color: #2563eb; margin-bottom: 6px;">
                            <?php echo osc_format_price($it['i_price']); ?>
                        </div>
                        <a href="<?php echo osc_esc_html($item_link); ?>" style="font-weight: 600; font-size: 14px; color: #1e293b; text-decoration: none; margin-bottom: 8px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            <?php echo osc_esc_html($it['s_title']); ?>
                        </a>
                        <div style="font-size: 12px; color: #64748b; margin-top: auto;">
                            <?php echo osc_esc_html(implode(', ', array_filter(array($it['s_city'] ?? '', $it['s_region'] ?? '')))); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php osc_current_web_theme_path('footer.php'); ?>
