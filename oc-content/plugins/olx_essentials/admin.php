<?php
if (!defined('OC_ADMIN') || OC_ADMIN !== true) exit('Access denied');

if (Params::getParam('plugin_action') == 'done') {
    osc_set_preference('whatsapp_enabled', Params::getParam('whatsapp_enabled') ? '1' : '0', 'olx_essentials', 'BOOLEAN');
    osc_set_preference('phone_mask_enabled', Params::getParam('phone_mask_enabled') ? '1' : '0', 'olx_essentials', 'BOOLEAN');
    osc_set_preference('leaflet_enabled', Params::getParam('leaflet_enabled') ? '1' : '0', 'olx_essentials', 'BOOLEAN');
    osc_set_preference('default_country_code', Params::getParam('default_country_code'), 'olx_essentials', 'STRING');

    osc_add_flash_ok_message(__('Settings saved successfully', 'olx_essentials'), 'admin');
    header('Location: ' . osc_admin_render_plugin_url('olx_essentials/admin.php'));
    exit;
}
?>

<div style="padding: 20px; max-width: 800px; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; margin-top: 20px;">
    <h2 style="margin-top: 0; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px;">OLX Essentials Configuration</h2>
    
    <form action="<?php echo osc_admin_render_plugin_url('olx_essentials/admin.php'); ?>" method="post">
        <input type="hidden" name="plugin_action" value="done" />
        
        <div style="margin-bottom: 20px;">
            <label style="font-weight: 600; display: block; margin-bottom: 6px;">WhatsApp Direct Contact Button</label>
            <input type="checkbox" name="whatsapp_enabled" value="1" <?php echo (osc_get_preference('whatsapp_enabled', 'olx_essentials') != '0' ? 'checked="checked"' : ''); ?> />
            <span>Enable 1-click WhatsApp messaging button on listing sidebar</span>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="font-weight: 600; display: block; margin-bottom: 6px;">Phone Masking (Anti-Scrape)</label>
            <input type="checkbox" name="phone_mask_enabled" value="1" <?php echo (osc_get_preference('phone_mask_enabled', 'olx_essentials') != '0' ? 'checked="checked"' : ''); ?> />
            <span>Mask seller phone numbers with a "Click to reveal" button to protect against automated scrapers</span>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="font-weight: 600; display: block; margin-bottom: 6px;">OpenStreetMap / Leaflet Integration</label>
            <input type="checkbox" name="leaflet_enabled" value="1" <?php echo (osc_get_preference('leaflet_enabled', 'olx_essentials') != '0' ? 'checked="checked"' : ''); ?> />
            <span>Display interactive location maps on listing details using OpenStreetMap</span>
        </div>

        <div style="margin-bottom: 25px;">
            <label style="font-weight: 600; display: block; margin-bottom: 6px;">Default Phone Country Code</label>
            <input type="text" name="default_country_code" value="<?php echo osc_esc_html(osc_get_preference('default_country_code', 'olx_essentials')); ?>" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 4px; width: 120px;" placeholder="55" />
            <small style="display: block; color: #64748b; margin-top: 4px;">Numeric prefix added to local phone numbers for WhatsApp links (e.g. 55 for Brazil, 1 for USA, 44 for UK).</small>
        </div>

        <button type="submit" class="btn btn-submit" style="background: #2563eb; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer;">Save Settings</button>
    </form>
</div>
