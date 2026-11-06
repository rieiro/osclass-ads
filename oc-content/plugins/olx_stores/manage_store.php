<?php
if (!osc_is_web_user_logged_in()) {
    header('Location: ' . osc_user_login_url());
    exit;
}

$user_id = osc_logged_user_id();
$dao = new DAO();
$dao->udb = DBConnectionClass::newInstance()->getOsclassDb();
$dao->dao = new DBCommandClass($dao->udb);

if (Params::getParam('save_store') == '1') {
    if (!osc_csrf_check()) {
        osc_add_flash_error_message(__('Security token validation failed. Please refresh and try again.', 'olx_stores'));
        header('Location: ' . osc_render_file_url('olx_stores/manage_store.php'));
        exit;
    }

    $store_name = trim(strip_tags(Params::getParam('s_store_name')));
    $tagline    = trim(strip_tags(Params::getParam('s_tagline')));
    $bio        = trim(strip_tags(Params::getParam('s_bio')));
    $phone      = trim(strip_tags(Params::getParam('s_phone')));
    $whatsapp   = trim(strip_tags(Params::getParam('s_whatsapp')));
    $address    = trim(strip_tags(Params::getParam('s_address')));
    $raw_slug   = Params::getParam('s_slug') ?: $store_name;
    $slug       = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '-', trim($raw_slug)));
    $slug       = trim(preg_replace('/-+/', '-', $slug), '-');

    if (empty($slug)) {
        $slug = 'store-' . $user_id;
    }

    if (empty($store_name)) {
        osc_add_flash_error_message(__('Store Name cannot be empty.', 'olx_stores'));
        header('Location: ' . osc_render_file_url('olx_stores/manage_store.php'));
        exit;
    }

    // Check slug collision against other stores
    $dao->dao->select('fk_i_user_id');
    $dao->dao->from(DB_TABLE_PREFIX . 't_olx_stores');
    $dao->dao->where('s_slug', $slug);
    $dao->dao->where('fk_i_user_id !=', $user_id);
    $collision_res = $dao->dao->get();

    if ($collision_res && $collision_res->numRows() > 0) {
        osc_add_flash_error_message(__('The slug "' . osc_esc_html($slug) . '" is already in use by another dealer. Please choose a different one.', 'olx_stores'));
        header('Location: ' . osc_render_file_url('olx_stores/manage_store.php'));
        exit;
    }

    $now = date('Y-m-d H:i:s');
    $storeData = array(
        's_slug'        => $slug,
        's_store_name'  => $store_name,
        's_tagline'     => $tagline,
        's_bio'         => $bio,
        's_phone'       => $phone,
        's_whatsapp'    => $whatsapp,
        's_address'     => $address
    );

    // Check if current user already has a store row
    $dao->dao->select('fk_i_user_id');
    $dao->dao->from(DB_TABLE_PREFIX . 't_olx_stores');
    $dao->dao->where('fk_i_user_id', $user_id);
    $check_res = $dao->dao->get();

    if ($check_res && $check_res->numRows() > 0) {
        $dao->dao->update(DB_TABLE_PREFIX . 't_olx_stores', $storeData, array('fk_i_user_id' => $user_id));
    } else {
        $storeData['fk_i_user_id'] = $user_id;
        $storeData['dt_created_at'] = $now;
        $dao->dao->insert(DB_TABLE_PREFIX . 't_olx_stores', $storeData);
    }

    osc_add_flash_ok_message(__('Pro Storefront updated successfully!', 'olx_stores'));
    header('Location: ' . osc_render_file_url('olx_stores/manage_store.php'));
    exit;
}

$dao->dao->select();
$dao->dao->from(DB_TABLE_PREFIX . 't_olx_stores');
$dao->dao->where('fk_i_user_id', $user_id);
$store_res = $dao->dao->get();
$store = ($store_res && $store_res->numRows() > 0) ? $store_res->row() : array();
?>

<div class="wrapper" style="margin-top: 25px;">
    <div style="background: #fff; border-radius: 8px; border: 1px solid #e2e8f0; padding: 25px; max-width: 700px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <h2 style="margin-top: 0; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">My Pro Storefront Settings</h2>

        <?php if (!empty($store['s_slug'])): ?>
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 12px; margin-bottom: 20px;">
                <strong>Public Store Link:</strong> 
                <a href="<?php echo osc_base_url(true) . '?store_slug=' . urlencode($store['s_slug']); ?>" target="_blank" style="color: #2563eb; font-weight: 600;">
                    <?php echo osc_base_url(true) . '?store_slug=' . osc_esc_html($store['s_slug']); ?> &rarr;
                </a>
            </div>
        <?php endif; ?>

        <form action="<?php echo osc_render_file_url('olx_stores/manage_store.php'); ?>" method="post">
            <?php echo osc_csrf_token_form(); ?>
            <input type="hidden" name="save_store" value="1" />

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Store Name *</label>
                <input type="text" name="s_store_name" value="<?php echo osc_esc_html($store['s_store_name'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;" required placeholder="e.g. SP Motors Dealership" />
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Custom URL Slug (leave blank to auto-generate)</label>
                <input type="text" name="s_slug" value="<?php echo osc_esc_html($store['s_slug'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;" placeholder="e.g. sp-motors" />
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Tagline / Slogan</label>
                <input type="text" name="s_tagline" value="<?php echo osc_esc_html($store['s_tagline'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;" placeholder="e.g. Premium Pre-owned Vehicles" />
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Business WhatsApp</label>
                <input type="text" name="s_whatsapp" value="<?php echo osc_esc_html($store['s_whatsapp'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;" placeholder="(11) 98765-4321" />
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Showroom Address</label>
                <input type="text" name="s_address" value="<?php echo osc_esc_html($store['s_address'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;" placeholder="Av. Paulista, 1000 - São Paulo, SP" />
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 5px;">About the Business</label>
                <textarea name="s_bio" rows="4" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;" placeholder="Tell buyers about your dealership or company..."><?php echo osc_esc_html($store['s_bio'] ?? ''); ?></textarea>
            </div>

            <button type="submit" style="background: #2563eb; color: #fff; padding: 12px 24px; border: none; border-radius: 6px; font-weight: 700; cursor: pointer;">Save Storefront</button>
        </form>
    </div>
</div>
