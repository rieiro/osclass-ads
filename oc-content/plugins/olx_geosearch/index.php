<?php
/*
Plugin Name: OLX GPS Radius Search
Plugin URI: https://github.com/osclass/
Description: OLX-style distance-based geolocation search using HTML5 GPS, bounding box spatial indexing, and Haversine formula calculation.
Version: 1.1.0
Author: Advanced Agentic Engineer
Author URI: https://osclass.org/
Short Name: olx_geosearch
Plugin update URI:
*/

function olx_geosearch_header() {
    if (osc_get_osclass_location() == 'search') {
        echo '<style>
            #sidebar .filters fieldset {
                display: block !important;
                float: left !important;
                clear: both !important;
                width: 100% !important;
                box-sizing: border-box !important;
                margin: 0 0 16px 0 !important;
            }
            #sidebar .filters fieldset.olx-geo-box,
            .olx-geo-box {
                display: block !important;
                float: left !important;
                clear: both !important;
                width: 100% !important;
                box-sizing: border-box !important;
                margin: 10px 0 16px 0 !important;
                padding: 0 0 16px 0 !important;
                border-bottom: 1px solid #e2e8f0 !important;
                position: relative !important;
            }
            #sidebar .filters .olx-geo-box h3,
            .olx-geo-box h3 {
                display: block !important;
                width: 100% !important;
                float: none !important;
                clear: both !important;
                margin: 0 0 8px 0 !important;
                font-size: 14px !important;
                font-weight: 700 !important;
                color: #1e293b !important;
                line-height: 1.2 !important;
            }
            #sidebar button.olx-geo-btn,
            #sidebar .olx-geo-btn,
            .olx-geo-btn {
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 8px !important;
                width: 100% !important;
                min-height: 40px !important;
                height: auto !important;
                padding: 8px 10px !important;
                box-sizing: border-box !important;
                clear: both !important;
                float: none !important;
                margin: 0 0 8px 0 !important;
                background: #f0fdf4 !important;
                color: #166534 !important;
                border: 1px solid #86efac !important;
                border-radius: 6px !important;
                font-size: 13px !important;
                font-weight: 700 !important;
                cursor: pointer !important;
                transition: all 0.2s ease-in-out !important;
                text-align: center !important;
            }
            #sidebar button.olx-geo-btn:hover,
            .olx-geo-btn:hover {
                background: #dcfce7 !important;
                border-color: #4ade80 !important;
                color: #14532d !important;
            }
            #sidebar button.olx-geo-btn svg {
                flex-shrink: 0 !important;
            }
            .olx-geo-status {
                display: block !important;
                clear: both !important;
                width: 100% !important;
                box-sizing: border-box !important;
                font-size: 11px !important;
                line-height: 1.3 !important;
                margin: 0 0 8px 0 !important;
                color: #64748b !important;
                word-break: break-word !important;
            }
            .olx-geo-status:empty {
                display: none !important;
            }
            .olx-geo-slider-wrapper {
                display: block !important;
                clear: both !important;
                width: 100% !important;
                box-sizing: border-box !important;
                margin-top: 10px !important;
                padding: 0 !important;
            }
            .olx-geo-slider-header {
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                width: 100% !important;
                box-sizing: border-box !important;
                font-size: 12px !important;
                color: #475569 !important;
                margin-bottom: 6px !important;
                clear: both !important;
            }
            .olx-geo-radius-val {
                font-weight: 700 !important;
                color: #15803d !important;
            }
            #sidebar input[type="range"].olx-geo-slider,
            .olx-geo-slider {
                display: block !important;
                clear: both !important;
                width: 100% !important;
                box-sizing: border-box !important;
                height: 6px !important;
                margin: 8px 0 !important;
                padding: 0 !important;
                background: #cbd5e1 !important;
                border-radius: 4px !important;
                outline: none !important;
                cursor: pointer !important;
                -webkit-appearance: none !important;
                appearance: none !important;
                border: none !important;
            }
            .olx-geo-slider::-webkit-slider-thumb {
                -webkit-appearance: none !important;
                appearance: none !important;
                width: 16px !important;
                height: 16px !important;
                border-radius: 50% !important;
                background: #16a34a !important;
                cursor: pointer !important;
                box-shadow: 0 1px 3px rgba(0,0,0,0.3) !important;
            }
            .olx-geo-slider::-moz-range-thumb {
                width: 16px !important;
                height: 16px !important;
                border-radius: 50% !important;
                background: #16a34a !important;
                cursor: pointer !important;
                border: none !important;
                box-shadow: 0 1px 3px rgba(0,0,0,0.3) !important;
            }
        </style>';
    }
}

function olx_geosearch_sidebar() {
    $user_lat = Params::getParam('user_lat');
    $user_lon = Params::getParam('user_lon');
    $user_radius = (int)Params::getParam('user_radius') ?: 25;
    ?>
    <fieldset class="olx-geo-box">
        <h3><?php _e('Distance from me (GPS)', 'olx_geosearch'); ?></h3>
        <input type="hidden" id="olx_user_lat" name="user_lat" value="<?php echo osc_esc_html($user_lat); ?>" />
        <input type="hidden" id="olx_user_lon" name="user_lon" value="<?php echo osc_esc_html($user_lon); ?>" />

        <button type="button" class="olx-geo-btn" id="olx-gps-btn" onclick="olxGetLocation()">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
            <span id="olx-gps-text"><?php echo ($user_lat && $user_lon) ? 'Location Active' : '📍 Use My Location'; ?></span>
        </button>
        <div id="olx-gps-status" class="olx-geo-status"></div>

        <div class="olx-geo-slider-wrapper">
            <div class="olx-geo-slider-header">
                <span>Radius:</span>
                <span class="olx-geo-radius-val"><span id="olx-radius-display"><?php echo $user_radius; ?></span> km</span>
            </div>
            <input type="range" class="olx-geo-slider" min="5" max="100" step="5" name="user_radius" id="olx_radius_slider" value="<?php echo $user_radius; ?>" oninput="document.getElementById('olx-radius-display').textContent = this.value;" />
        </div>
    </fieldset>

    <script>
    function olxGetLocation() {
        var status = document.getElementById('olx-gps-status');
        var btnText = document.getElementById('olx-gps-text');
        if (!navigator.geolocation) {
            status.textContent = 'Geolocation is not supported by your browser.';
            return;
        }

        btnText.textContent = 'Detecting...';
        navigator.geolocation.getCurrentPosition(function(pos) {
            document.getElementById('olx_user_lat').value = pos.coords.latitude.toFixed(6);
            document.getElementById('olx_user_lon').value = pos.coords.longitude.toFixed(6);
            btnText.textContent = 'Location Set!';
            status.textContent = 'Coordinates: ' + pos.coords.latitude.toFixed(3) + ', ' + pos.coords.longitude.toFixed(3);

            // Auto-trigger search filter submit
            var form = document.querySelector('form.search-side-form') || document.querySelector('#sidebar form') || document.querySelector('form[action*="search"]');
            if (form) {
                form.submit();
            }
        }, function(err) {
            btnText.textContent = '📍 Use My Location';
            status.textContent = 'Location access denied or unavailable.';
        });
    }
    </script>
    <?php
}

// Bounding Box + Haversine formula search filter
function olx_geosearch_filter($conditions, $sql_type) {
    $lat = (float)Params::getParam('user_lat');
    $lon = (float)Params::getParam('user_lon');
    $radius = (float)Params::getParam('user_radius') ?: 25.0;

    if ($lat != 0.0 && $lon != 0.0 && $radius > 0) {
        // 1 degree latitude ~ 111.045 km
        $d_lat = $radius / 111.045;
        // 1 degree longitude ~ 111.045 * cos(lat) km
        $cos_lat = max(0.01, cos(deg2rad($lat)));
        $d_lon = $radius / (111.045 * $cos_lat);

        $min_lat = $lat - $d_lat;
        $max_lat = $lat + $d_lat;
        $min_lon = $lon - $d_lon;
        $max_lon = $lon + $d_lon;

        // Bounding Box enables MariaDB idx_item_coords index range scan before evaluating trigonometric acos
        $conditions[] = sprintf(
            "(loc.d_coord_lat IS NOT NULL AND loc.d_coord_long IS NOT NULL 
              AND loc.d_coord_lat BETWEEN %f AND %f 
              AND loc.d_coord_long BETWEEN %f AND %f 
              AND (6371 * acos(LEAST(1.0, GREATEST(-1.0, cos(radians(%f)) * cos(radians(loc.d_coord_lat)) * cos(radians(loc.d_coord_long) - radians(%f)) + sin(radians(%f)) * sin(radians(loc.d_coord_lat)))))) <= %f)",
            $min_lat, $max_lat, $min_lon, $max_lon,
            $lat, $lon, $lat, $radius
        );
    }
    return $conditions;
}

// Hook registrations
osc_add_hook('header', 'olx_geosearch_header');
osc_add_hook('search_sidebar_location', 'olx_geosearch_sidebar');
osc_add_filter('sql_search_conditions', 'olx_geosearch_filter');
