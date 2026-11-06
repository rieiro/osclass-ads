<?php
if (!defined('ABS_PATH')) exit('ABS_PATH is not loaded. Direct access is not allowed.');

$slug = trim((string)Params::getParam('slug'));
$bp = BusinessProfile::newInstance();
$profile = $bp->findBySlug($slug);

if (!$profile || (int)($profile['b_enabled'] ?? 0) !== 1) {
    header("HTTP/1.0 404 Not Found");
    osc_add_flash_error_message(__('Business profile not found or inactive.', 'business_profile'));
    if (file_exists(osc_themes_path() . osc_theme() . '/404.php')) {
        require_once osc_themes_path() . osc_theme() . '/404.php';
    } else {
        echo '<h1>404 Not Found</h1><p>Business profile does not exist.</p>';
    }
    if (!defined('OC_TEST_MODE') || !OC_TEST_MODE) {
        exit;
    }
    return;
}

$user_id = (int)$profile['fk_i_user_id'];
$hours = !empty($profile['s_opening_hours']) ? json_decode((string)$profile['s_opening_hours'], true) : [];
$socials = !empty($profile['s_social_links']) ? json_decode((string)$profile['s_social_links'], true) : [];
$attributes = !empty($profile['s_attributes']) ? json_decode((string)$profile['s_attributes'], true) : [];
$is_open = BusinessProfile::calculateIsOpenNow(is_array($hours) ? $hours : []);

// Fetch active inventory for this business
$dao = new DAO();
$dao->udb = DBConnectionClass::newInstance()->getOsclassDb();
$dao->dao = new DBCommandClass($dao->udb);
$dao->dao->select('i.pk_i_id, i.dt_pub_date, i.i_price, i.fk_c_currency_code, d.s_title, l.s_city, l.s_region');
$dao->dao->from(DB_TABLE_PREFIX . 't_item i');
$dao->dao->join(DB_TABLE_PREFIX . 't_item_description d', 'd.fk_i_item_id = i.pk_i_id', 'LEFT');
$dao->dao->join(DB_TABLE_PREFIX . 't_item_location l', 'l.fk_i_item_id = i.pk_i_id', 'LEFT');
$dao->dao->where('i.fk_i_user_id', $user_id);
$dao->dao->where('i.b_active', 1);
$dao->dao->where('i.b_enabled', 1);
$dao->dao->where('i.b_spam', 0);
$dao->dao->orderBy('i.dt_pub_date', 'DESC');
$dao->dao->limit(50);
$items_res = $dao->dao->get();
$items = ($items_res && $items_res->numRows() > 0) ? $items_res->result() : [];

osc_current_web_theme_path('header.php');
?>

<div class="bp-storefront" style="max-width: 1200px; margin: 25px auto; padding: 0 15px; font-family: system-ui, -apple-system, sans-serif;">
    <!-- Hero Banner & Header -->
    <div style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 25px;">
        <?php if (!empty($profile['s_banner_url'])): ?>
            <div style="height: 220px; background-image: url('<?php echo osc_esc_html($profile['s_banner_url']); ?>'); background-size: cover; background-position: center;"></div>
        <?php else: ?>
            <div style="height: 140px; background: linear-gradient(90deg, #4338ca, #6366f1);"></div>
        <?php endif; ?>

        <div style="padding: 24px 30px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 20px; color: #fff;">
            <div style="display: flex; align-items: center; gap: 20px;">
                <?php if (!empty($profile['s_logo_url'])): ?>
                    <img src="<?php echo osc_esc_html($profile['s_logo_url']); ?>" alt="Logo" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                <?php else: ?>
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: #4f46e5; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: bold; border: 3px solid #fff;">
                        <?php echo strtoupper(substr($profile['s_company_name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>

                <div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <h1 style="margin: 0; font-size: 26px; font-weight: 800; color: #fff;"><?php echo osc_esc_html($profile['s_company_name']); ?></h1>
                        <?php if ((int)$profile['b_verified'] === 1): ?>
                            <span style="background: #22c55e; color: #fff; font-size: 11px; font-weight: 800; text-transform: uppercase; padding: 3px 8px; border-radius: 20px; letter-spacing: 0.5px;">&#10004; Verified</span>
                        <?php endif; ?>
                        <?php if ($is_open): ?>
                            <span style="background: #10b981; color: #fff; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 20px;">Open Now</span>
                        <?php else: ?>
                            <span style="background: #64748b; color: #fff; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 20px;">Closed</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($profile['s_tagline'])): ?>
                        <p style="margin: 4px 0 0 0; color: #c7d2fe; font-size: 15px;"><?php echo osc_esc_html($profile['s_tagline']); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Contact CTAs -->
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <?php if (!empty($profile['s_whatsapp'])): 
                    $clean_wa = preg_replace('/[^0-9]/', '', (string)$profile['s_whatsapp']);
                ?>
                    <a href="https://wa.me/55<?php echo $clean_wa; ?>" target="_blank" rel="noopener noreferrer" style="background: #22c55e; color: #fff; text-decoration: none; padding: 10px 18px; border-radius: 8px; font-weight: 700; font-size: 14px; display: inline-flex; align-items: center; gap: 6px;">
                        WhatsApp
                    </a>
                <?php endif; ?>
                <?php if (!empty($profile['s_phone'])): ?>
                    <a href="tel:<?php echo osc_esc_html($profile['s_phone']); ?>" style="background: rgba(255,255,255,0.15); color: #fff; text-decoration: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 14px;">
                        <?php echo osc_esc_html($profile['s_phone']); ?>
                    </a>
                <?php endif; ?>
                <?php if (!empty($profile['s_website'])): ?>
                    <a href="<?php echo osc_esc_html($profile['s_website']); ?>" target="_blank" rel="noopener noreferrer" style="background: #4f46e5; color: #fff; text-decoration: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 14px;">
                        Website &rarr;
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($profile['s_bio'])): ?>
            <div style="padding: 16px 30px; background: rgba(0,0,0,0.15); border-top: 1px solid rgba(255,255,255,0.1); color: #e0e7ff; font-size: 14px; line-height: 1.6;">
                <?php echo nl2br(osc_esc_html($profile['s_bio'])); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Grid: Sidebar Details + Inventory -->
    <div style="display: grid; grid-template-columns: 320px 1fr; gap: 25px;">
        <!-- Left Sidebar: Hours, Location, Amenities -->
        <div>
            <!-- Location & Address Card -->
            <?php if (!empty($profile['s_address'])): ?>
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <h3 style="margin: 0 0 10px 0; font-size: 15px; color: #0f172a; font-weight: 700;">Location</h3>
                    <p style="margin: 0; font-size: 14px; color: #475569; line-height: 1.5;"><?php echo osc_esc_html($profile['s_address']); ?></p>
                </div>
            <?php endif; ?>

            <!-- Opening Hours Card -->
            <?php if (!empty($hours) && is_array($hours)): ?>
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <h3 style="margin: 0 0 12px 0; font-size: 15px; color: #0f172a; font-weight: 700;">Opening Hours</h3>
                    <table style="width: 100%; font-size: 13px; color: #334155; border-collapse: collapse;">
                        <?php 
                        $dayNames = ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday'];
                        foreach ($dayNames as $dKey => $dLabel):
                            $sch = $hours[$dKey] ?? null;
                        ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 6px 0; font-weight: 600;"><?php echo $dLabel; ?></td>
                                <td style="padding: 6px 0; text-align: right; color: <?php echo (!empty($sch['closed']) ? '#ef4444' : '#0f172a'); ?>;">
                                    <?php 
                                    if (!$sch || !empty($sch['closed'])) {
                                        echo 'Closed';
                                    } else {
                                        echo osc_esc_html(($sch['open'] ?? '') . ' - ' . ($sch['close'] ?? ''));
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Amenities & Features Card -->
            <?php if (!empty($attributes) && is_array($attributes)): ?>
                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <h3 style="margin: 0 0 10px 0; font-size: 15px; color: #0f172a; font-weight: 700;">Features & Services</h3>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <?php foreach ($attributes as $attrKey => $attrVal): if ($attrVal): ?>
                            <span style="background: #f1f5f9; color: #334155; font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 6px;">
                                &#10003; <?php echo osc_esc_html(ucfirst((string)$attrKey)); ?>
                            </span>
                        <?php endif; endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Side: Active Inventory -->
        <div>
            <div style="margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0; font-size: 20px; font-weight: 800; color: #0f172a;">
                    Inventory (<?php echo count($items); ?> Listings)
                </h2>
            </div>

            <?php if (empty($items)): ?>
                <div style="background: #fff; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 40px; text-align: center; color: #64748b;">
                    No active listings posted by this business yet.
                </div>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 18px;">
                    <?php foreach ($items as $it): 
                        $item_url = osc_item_url_ns((int)$it['pk_i_id']);
                    ?>
                        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column;">
                            <div style="padding: 16px; flex: 1; display: flex; flex-direction: column;">
                                <div style="font-size: 18px; font-weight: 800; color: #2563eb; margin-bottom: 6px;">
                                    <?php echo osc_format_price($it['i_price'], $it['fk_c_currency_code']); ?>
                                </div>
                                <a href="<?php echo osc_esc_html($item_url); ?>" style="font-weight: 700; font-size: 15px; color: #0f172a; text-decoration: none; margin-bottom: 8px;">
                                    <?php echo osc_esc_html($it['s_title']); ?>
                                </a>
                                <div style="font-size: 12px; color: #64748b; margin-top: auto;">
                                    <?php echo osc_esc_html(implode(', ', array_filter([$it['s_city'] ?? '', $it['s_region'] ?? '']))); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php osc_current_web_theme_path('footer.php'); ?>
