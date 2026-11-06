<?php
/*
Plugin Name: OLX Essentials
Plugin URI: https://github.com/osclass/
Description: OLX-style features for Osclass: WhatsApp 1-click button, anti-scrape phone mask, and OpenStreetMap Leaflet integration.
Version: 1.0.0
Author: Advanced Agentic Engineer
Author URI: https://osclass.org/
Short Name: olx_essentials
Plugin update URI: 
*/

function olx_essentials_install() {
    osc_set_preference('whatsapp_enabled', '1', 'olx_essentials', 'BOOLEAN');
    osc_set_preference('phone_mask_enabled', '1', 'olx_essentials', 'BOOLEAN');
    osc_set_preference('leaflet_enabled', '1', 'olx_essentials', 'BOOLEAN');
    osc_set_preference('default_country_code', '55', 'olx_essentials', 'STRING'); // Default country code for phone format
}

function olx_essentials_uninstall() {
    Preference::newInstance()->delete(array('s_section' => 'olx_essentials'));
}

// Enqueue Leaflet CSS/JS on item page
function olx_essentials_header() {
    if (osc_is_ad_page()) {
        echo '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>' . "\n";
        echo '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>' . "\n";
        echo '<style>
            .olx-contact-buttons { margin-top: 15px; display: flex; flex-direction: column; gap: 10px; }
            .olx-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 18px; border-radius: 6px; font-weight: bold; font-size: 15px; text-decoration: none !important; cursor: pointer; transition: all 0.2s ease; border: none; width: 100%; box-sizing: border-box; text-align: center; }
            .olx-btn-whatsapp { background-color: #25D366; color: #ffffff !important; box-shadow: 0 2px 6px rgba(37,211,102,0.3); }
            .olx-btn-whatsapp:hover { background-color: #1eb956; color: #fff !important; transform: translateY(-1px); }
            .olx-btn-phone { background-color: #f6f6f9; color: #1a1a24 !important; border: 1px solid #d5d5db; }
            .olx-btn-phone:hover { background-color: #e9e9f0; }
            .olx-phone-revealed { font-size: 16px; font-weight: bold; color: #2e384d; letter-spacing: 0.5px; }
            .olx-map-container { margin: 25px 0; background: #fff; border-radius: 8px; border: 1px solid #e2e8f0; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
            .olx-map-title { font-size: 18px; font-weight: 700; color: #1e293b; margin: 0 0 12px 0; display: flex; align-items: center; gap: 8px; }
            #olx-item-map { height: 280px; width: 100%; border-radius: 6px; z-index: 1; }
        </style>' . "\n";
    }
}

// Sidebar buttons (WhatsApp + Reveal Phone)
function olx_essentials_sidebar_buttons() {
    $phone = osc_item_contact_phone();
    if (empty($phone)) {
        return;
    }

    $raw_digits = preg_replace('/[^0-9]/', '', $phone);
    $country_code = osc_get_preference('default_country_code', 'olx_essentials');
    if (empty($country_code)) {
        $country_code = '55';
    }

    // Prepare WhatsApp number with country code if not present
    $wa_number = $raw_digits;
    if (strlen($wa_number) <= 11 && substr($wa_number, 0, strlen($country_code)) !== $country_code) {
        $wa_number = $country_code . $wa_number;
    }

    $item_title = osc_item_title();
    $item_url = osc_item_url();
    $wa_text = rawurlencode("Hello! I am contacting you regarding your listing: \"{$item_title}\" on " . osc_page_title() . "\n{$item_url}");
    $wa_link = "https://wa.me/{$wa_number}?text={$wa_text}";

    // Mask phone (e.g. (11) 98765-4321 -> (11) 98765-••••)
    $masked_phone = $phone;
    if (strlen($phone) > 4) {
        $masked_phone = substr($phone, 0, -4) . '••••';
    }

    echo '<div class="olx-contact-buttons">';
    
    // WhatsApp button
    if (osc_get_preference('whatsapp_enabled', 'olx_essentials') != '0') {
        echo '<a href="' . osc_esc_html($wa_link) . '" target="_blank" rel="noopener noreferrer" class="olx-btn olx-btn-whatsapp">';
        echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.669-.699c.969.54 1.772.82 2.791.82 3.181 0 5.767-2.586 5.768-5.766 0-3.18-2.586-5.766-5.768-5.766zm9.969 5.766c0 5.503-4.497 10-10 10-1.748 0-3.387-.45-4.819-1.238l-7.181 1.88 1.91-6.974c-.9-1.488-1.428-3.23-1.428-5.1 0-5.503 4.497-10 10-10 5.503 0 10 4.497 10 10zm-4.708 3.551c-.261-.131-1.545-.762-1.785-.85-.24-.087-.414-.131-.588.131s-.676.85-.828 1.025c-.153.174-.305.196-.566.065-.262-.13-1.104-.407-2.102-1.298-.778-.694-1.303-1.552-1.455-1.814-.153-.261-.016-.402.115-.532.118-.117.262-.305.393-.458.131-.153.174-.262.261-.436.088-.175.044-.327-.021-.458-.066-.131-.588-1.417-.806-1.942-.213-.511-.429-.441-.588-.449-.153-.008-.327-.01-.501-.01-.174 0-.457.065-.697.327-.24.261-.915.894-.915 2.18s.937 2.528 1.068 2.703c.131.174 1.844 2.815 4.468 3.947.624.27 1.11.431 1.49.552.627.2 1.198.172 1.648.105.502-.075 1.545-.632 1.763-1.242.218-.611.218-1.134.153-1.243-.065-.109-.239-.174-.5-.305z"/></svg>';
        echo 'Chat on WhatsApp</a>';
    }

    // Anti-scraping click-to-reveal phone button
    if (osc_get_preference('phone_mask_enabled', 'olx_essentials') != '0') {
        $encoded_phone = base64_encode($phone);
        echo '<button type="button" class="olx-btn olx-btn-phone" id="olx-phone-btn" data-phone="' . $encoded_phone . '" onclick="olxRevealPhone(this)">';
        echo '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"></path></svg>';
        echo '<span id="olx-phone-display">' . osc_esc_html($masked_phone) . ' (Click to reveal)</span>';
        echo '</button>';
        echo '<script>
        function olxRevealPhone(btn) {
            try {
                var raw = atob(btn.getAttribute("data-phone"));
                document.getElementById("olx-phone-display").innerHTML = "<a href=\"tel:" + raw.replace(/[^0-9+]/g, "") + "\" class=\"olx-phone-revealed\">" + raw + "</a>";
                btn.removeAttribute("onclick");
                btn.style.cursor = "default";
            } catch(e) {}
        }
        </script>';
    }

    echo '</div>';
}

// Location Leaflet Map in Item Detail
function olx_essentials_item_map() {
    if (osc_get_preference('leaflet_enabled', 'olx_essentials') == '0') {
        return;
    }

    $item = osc_item();
    $loc = array();
    if (!empty($item['s_address'])) $loc[] = $item['s_address'];
    if (!empty($item['s_city_area'])) $loc[] = $item['s_city_area'];
    if (!empty($item['s_city'])) $loc[] = $item['s_city'];
    if (!empty($item['s_region'])) $loc[] = $item['s_region'];
    if (!empty($item['s_country'])) $loc[] = $item['s_country'];

    if (empty($loc)) {
        return;
    }

    $query = implode(', ', $loc);
    $lat = !empty($item['d_coord_lat']) ? (float)$item['d_coord_lat'] : null;
    $long = !empty($item['d_coord_long']) ? (float)$item['d_coord_long'] : null;

    echo '<div class="olx-map-container">';
    echo '<h3 class="olx-map-title">';
    echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>';
    echo 'Location: ' . osc_esc_html(implode(', ', array_filter(array(osc_item_city(), osc_item_region()))));
    echo '</h3>';
    echo '<div id="olx-item-map"></div>';
    echo '</div>';

    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        var mapElement = document.getElementById("olx-item-map");
        if (!mapElement) return;

        var lat = ' . ($lat !== null ? $lat : 'null') . ';
        var lon = ' . ($long !== null ? $long : 'null') . ';

        function renderMap(latitude, longitude, zoom) {
            var map = L.map("olx-item-map").setView([latitude, longitude], zoom || 13);
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                attribution: "&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors"
            }).addTo(map);

            var circle = L.circle([latitude, longitude], {
                color: "#2563eb",
                fillColor: "#3b82f6",
                fillOpacity: 0.25,
                radius: 1200
            }).addTo(map);

            var marker = L.marker([latitude, longitude]).addTo(map);
            marker.bindPopup("<b>Approximate area</b><br>' . osc_esc_js($query) . '").openPopup();
        }

        if (lat && lon && lat !== 0 && lon !== 0) {
            renderMap(lat, lon, 14);
        } else {
            // Geocode using OpenStreetMap Nominatim
            var geocodeUrl = "https://nominatim.openstreetmap.org/search?format=json&q=" + encodeURIComponent("' . osc_esc_js($query) . '");
            fetch(geocodeUrl, {
                headers: { "Accept": "application/json" }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data && data.length > 0) {
                    renderMap(parseFloat(data[0].lat), parseFloat(data[0].lon), 13);
                } else {
                    // Fallback to City, Country
                    var fallbackQuery = "' . osc_esc_js(osc_item_city() . ', ' . osc_item_country()) . '";
                    return fetch("https://nominatim.openstreetmap.org/search?format=json&q=" + encodeURIComponent(fallbackQuery))
                    .then(function(r) { return r.json(); })
                    .then(function(d2) {
                        if (d2 && d2.length > 0) {
                            renderMap(parseFloat(d2[0].lat), parseFloat(d2[0].lon), 11);
                        } else {
                            var box = mapElement.closest ? mapElement.closest(".olx-map-container") : mapElement.parentElement;
                            if (box) box.style.display = "none"; else mapElement.style.display = "none";
                        }
                    });
                }
            })
            .catch(function(err) {
                var box = mapElement.closest ? mapElement.closest(".olx-map-container") : mapElement.parentElement;
                if (box) box.style.display = "none"; else mapElement.style.display = "none";
            });
        }
    });
    </script>';
}

// Admin configuration page
function olx_essentials_admin_menu() {
    osc_add_admin_menu_page(
        __('OLX Essentials', 'olx_essentials'),
        osc_admin_render_plugin_url('olx_essentials/admin.php'),
        'olx_essentials_admin',
        'administrator'
    );
}

// Hook registrations
osc_register_plugin(osc_plugin_path(__FILE__), 'olx_essentials_install');
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'olx_essentials_uninstall');

osc_add_hook('header', 'olx_essentials_header');
osc_add_hook('item_sidebar_top', 'olx_essentials_sidebar_buttons');
osc_add_hook('location', 'olx_essentials_item_map');
osc_add_hook('admin_menu_init', 'olx_essentials_admin_menu');

// Collation alignment hook for B-Tree index safety
function olx_enforce_collation() {
    $dao = new DAO();
    $dao->udb = DBConnectionClass::newInstance()->getOsclassDb();
    if ($dao->udb) {
        @$dao->udb->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
    }
}
osc_add_hook('before_init', 'olx_enforce_collation');
osc_add_hook('init', 'olx_enforce_collation');
