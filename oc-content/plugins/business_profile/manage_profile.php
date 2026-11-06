<?php
if (!defined('ABS_PATH')) exit('ABS_PATH is not loaded. Direct access is not allowed.');

if (!osc_is_web_user_logged_in()) {
    osc_add_flash_error_message(__('You must be logged in to manage your business profile.', 'business_profile'));
    header('Location: ' . osc_user_login_url());
    if (!defined('OC_TEST_MODE') || !OC_TEST_MODE) {
        exit;
    }
    return;
}

$userId = (int)osc_logged_user_id();
$bp = BusinessProfile::newInstance();
$profile = $bp->findByUserId($userId) ?: [];

// Handle Form Submission
if (Params::getParam('action') === 'save_business_profile') {
    if (function_exists('osc_csrf_check')) {
        osc_csrf_check();
    }

    $slug = strtolower(trim((string)Params::getParam('s_slug')));
    $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
    $slug = trim($slug, '-');
    if (strlen($slug) < 3) {
        $slug = 'store-' . $userId;
    }

    // Check if slug taken by another user
    $existingSlug = $bp->findBySlug($slug);
    if ($existingSlug && (int)$existingSlug['fk_i_user_id'] !== $userId) {
        osc_add_flash_error_message(__('This store slug is already in use by another business. Please choose another.', 'business_profile'));
    } else {
        // Build Opening Hours JSON
        $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        $hoursData = [];
        foreach ($days as $d) {
            $isClosed = (bool)Params::getParam('hours_' . $d . '_closed');
            $hoursData[$d] = [
                'closed' => $isClosed,
                'open' => trim((string)Params::getParam('hours_' . $d . '_open')) ?: '09:00',
                'close' => trim((string)Params::getParam('hours_' . $d . '_close')) ?: '18:00',
            ];
        }

        // Build Social Links JSON
        $socialsData = [
            'instagram' => trim((string)Params::getParam('social_instagram')),
            'facebook' => trim((string)Params::getParam('social_facebook')),
            'linkedin' => trim((string)Params::getParam('social_linkedin')),
            'youtube' => trim((string)Params::getParam('social_youtube')),
            'x' => trim((string)Params::getParam('social_x')),
        ];

        // Build Attributes JSON
        $attributesData = [
            'wifi' => (bool)Params::getParam('attr_wifi'),
            'parking' => (bool)Params::getParam('attr_parking'),
            'delivery' => (bool)Params::getParam('attr_delivery'),
            'card' => (bool)Params::getParam('attr_card'),
            'pix' => (bool)Params::getParam('attr_pix'),
        ];

        $dataToSave = [
            's_slug' => $slug,
            's_company_name' => trim((string)Params::getParam('s_company_name')) ?: osc_logged_user_name(),
            's_tagline' => trim((string)Params::getParam('s_tagline')),
            's_bio' => trim((string)Params::getParam('s_bio')),
            's_logo_url' => trim((string)Params::getParam('s_logo_url')),
            's_banner_url' => trim((string)Params::getParam('s_banner_url')),
            's_phone' => trim((string)Params::getParam('s_phone')),
            's_whatsapp' => trim((string)Params::getParam('s_whatsapp')),
            's_website' => trim((string)Params::getParam('s_website')),
            's_email' => trim((string)Params::getParam('s_email')),
            's_address' => trim((string)Params::getParam('s_address')),
            'd_coord_lat' => is_numeric(Params::getParam('d_coord_lat')) ? (float)Params::getParam('d_coord_lat') : null,
            'd_coord_long' => is_numeric(Params::getParam('d_coord_long')) ? (float)Params::getParam('d_coord_long') : null,
            's_opening_hours' => json_encode($hoursData),
            's_social_links' => json_encode($socialsData),
            's_attributes' => json_encode($attributesData),
            'b_enabled' => Params::getParam('b_enabled') !== null ? 1 : 0,
        ];

        $bp->saveProfile($userId, $dataToSave);
        osc_add_flash_ok_message(__('Your business profile has been saved successfully.', 'business_profile'));
        header('Location: ' . osc_render_file_url('business_profile/manage_profile.php'));
        if (!defined('OC_TEST_MODE') || !OC_TEST_MODE) {
            exit;
        }
        return;
    }
}

$savedHours = !empty($profile['s_opening_hours']) ? json_decode((string)$profile['s_opening_hours'], true) : [];
$savedSocials = !empty($profile['s_social_links']) ? json_decode((string)$profile['s_social_links'], true) : [];
$savedAttrs = !empty($profile['s_attributes']) ? json_decode((string)$profile['s_attributes'], true) : [];


?>

<div class="bp-manage-form" style="max-width: 900px; margin: 30px auto; padding: 25px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); font-family: system-ui, -apple-system, sans-serif;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 16px; margin-bottom: 24px;">
        <div>
            <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: #0f172a;">Business Profile Management</h1>
            <p style="margin: 4px 0 0 0; color: #64748b; font-size: 14px;">Customize your digital storefront, opening hours, and verified branding.</p>
        </div>
        <?php if (!empty($profile['s_slug'])): ?>
            <a href="<?php echo osc_base_url(true); ?>?page=business&slug=<?php echo urlencode($profile['s_slug']); ?>" target="_blank" style="background: #4f46e5; color: #fff; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-weight: 700; font-size: 13px;">
                View Live Store &rarr;
            </a>
        <?php endif; ?>
    </div>

    <form action="<?php echo osc_render_file_url('business_profile/manage_profile.php'); ?>" method="post">
        <?php osc_csrf_token_form(); ?>
        <input type="hidden" name="action" value="save_business_profile" />

        <!-- 1. Identity & Branding -->
        <h2 style="font-size: 17px; font-weight: 700; color: #1e293b; margin: 0 0 16px 0; border-left: 4px solid #4f46e5; padding-left: 10px;">Company Branding</h2>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">Company / Store Name *</label>
                <input type="text" name="s_company_name" value="<?php echo osc_esc_html($profile['s_company_name'] ?? osc_logged_user_name()); ?>" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;" />
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">Store URL Slug (unique) *</label>
                <input type="text" name="s_slug" value="<?php echo osc_esc_html($profile['s_slug'] ?? ''); ?>" placeholder="e.g. joao-motors" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;" />
            </div>
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">Tagline / Slogan</label>
            <input type="text" name="s_tagline" value="<?php echo osc_esc_html($profile['s_tagline'] ?? ''); ?>" placeholder="e.g. Authorized dealer since 2010" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;" />
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">About / Company Bio</label>
            <textarea name="s_bio" rows="4" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;"><?php echo osc_esc_html($profile['s_bio'] ?? ''); ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">Logo Image URL</label>
                <input type="url" name="s_logo_url" value="<?php echo osc_esc_html($profile['s_logo_url'] ?? ''); ?>" placeholder="https://..." style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;" />
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">Cover Banner URL</label>
                <input type="url" name="s_banner_url" value="<?php echo osc_esc_html($profile['s_banner_url'] ?? ''); ?>" placeholder="https://..." style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;" />
            </div>
        </div>

        <!-- 2. Contacts & Location -->
        <h2 style="font-size: 17px; font-weight: 700; color: #1e293b; margin: 0 0 16px 0; border-left: 4px solid #4f46e5; padding-left: 10px;">Contact & Location</h2>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">WhatsApp Number (for 1-click chat)</label>
                <input type="text" name="s_whatsapp" value="<?php echo osc_esc_html($profile['s_whatsapp'] ?? ''); ?>" placeholder="11988887777" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;" />
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">Landline Phone</label>
                <input type="text" name="s_phone" value="<?php echo osc_esc_html($profile['s_phone'] ?? ''); ?>" placeholder="1133334444" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;" />
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">Official Website</label>
                <input type="url" name="s_website" value="<?php echo osc_esc_html($profile['s_website'] ?? ''); ?>" placeholder="https://mycompany.com" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;" />
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">Public Email</label>
                <input type="email" name="s_email" value="<?php echo osc_esc_html($profile['s_email'] ?? ''); ?>" placeholder="contact@mycompany.com" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;" />
            </div>
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">Physical Address</label>
            <input type="text" name="s_address" value="<?php echo osc_esc_html($profile['s_address'] ?? ''); ?>" placeholder="Av. Paulista, 1000 - Sao Paulo, SP" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;" />
        </div>

        <!-- 3. Opening Hours Schedule -->
        <h2 style="font-size: 17px; font-weight: 700; color: #1e293b; margin: 0 0 16px 0; border-left: 4px solid #4f46e5; padding-left: 10px;">Opening Hours</h2>
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
            <?php 
            $dayLabels = ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday'];
            foreach ($dayLabels as $dKey => $dLabel):
                $sch = $savedHours[$dKey] ?? [];
                $closed = !empty($sch['closed']);
                $openT = $sch['open'] ?? '09:00';
                $closeT = $sch['close'] ?? '18:00';
            ?>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #edf2f7;">
                    <div style="width: 120px; font-weight: 600; font-size: 13px; color: #334155;"><?php echo $dLabel; ?></div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="time" name="hours_<?php echo $dKey; ?>_open" value="<?php echo osc_esc_html($openT); ?>" style="padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 4px;" />
                        <span>to</span>
                        <input type="time" name="hours_<?php echo $dKey; ?>_close" value="<?php echo osc_esc_html($closeT); ?>" style="padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 4px;" />
                        <label style="font-size: 12px; color: #64748b; margin-left: 10px; display: inline-flex; align-items: center; gap: 4px;">
                            <input type="checkbox" name="hours_<?php echo $dKey; ?>_closed" value="1" <?php echo ($closed ? 'checked' : ''); ?> /> Closed
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 4. Features & Amenities -->
        <h2 style="font-size: 17px; font-weight: 700; color: #1e293b; margin: 0 0 16px 0; border-left: 4px solid #4f46e5; padding-left: 10px;">Features & Amenities</h2>
        <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 24px; background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
            <label style="font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                <input type="checkbox" name="attr_wifi" value="1" <?php echo (!empty($savedAttrs['wifi']) ? 'checked' : ''); ?> /> Free Wi-Fi
            </label>
            <label style="font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                <input type="checkbox" name="attr_parking" value="1" <?php echo (!empty($savedAttrs['parking']) ? 'checked' : ''); ?> /> Customer Parking
            </label>
            <label style="font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                <input type="checkbox" name="attr_delivery" value="1" <?php echo (!empty($savedAttrs['delivery']) ? 'checked' : ''); ?> /> Delivery Service
            </label>
            <label style="font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                <input type="checkbox" name="attr_card" value="1" <?php echo (!empty($savedAttrs['card']) ? 'checked' : ''); ?> /> Accepts Credit Cards
            </label>
            <label style="font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                <input type="checkbox" name="attr_pix" value="1" <?php echo (!empty($savedAttrs['pix']) ? 'checked' : ''); ?> /> Accepts PIX
            </label>
        </div>

        <div style="margin-bottom: 24px; padding-top: 15px; border-top: 1px solid #e2e8f0;">
            <label style="font-size: 14px; font-weight: 700; color: #0f172a; display: inline-flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="b_enabled" value="1" <?php echo ((int)($profile['b_enabled'] ?? 1) === 1 ? 'checked' : ''); ?> />
                Business Profile Active (visible to visitors)
            </label>
        </div>

        <button type="submit" style="background: #22c55e; color: #fff; font-size: 16px; font-weight: 800; padding: 12px 30px; border: none; border-radius: 8px; cursor: pointer; box-shadow: 0 2px 6px rgba(34,197,94,0.3);">
            Save Business Profile
        </button>
    </form>
</div>


