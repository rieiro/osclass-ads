<?php
/*
Plugin Name: OLX Traffic & Log Analytics
Plugin URI: https://github.com/osclass/
Description: Integrates GoAccess real-time visual web analytics directly into the Osclass Admin navigation.
Version: 1.0.0
Author: Advanced Agentic Engineer
Author URI: https://osclass.org/
Short Name: olx_analytics
Plugin update URI:
*/

function olx_analytics_menu() {
    osc_add_admin_menu_page(
        __('Traffic Analytics', 'olx_analytics'),
        osc_base_url() . 'oc-admin/reports/traffic.html',
        'olx_analytics',
        'administrator'
    );
}

osc_add_hook('admin_menu_init', 'olx_analytics_menu');
