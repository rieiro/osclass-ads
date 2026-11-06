<?php
/*
Plugin Name: Business Profile
Plugin URI: https://osclasspoint.com/osclass-plugins/design-interface/business-profile-osclass-plugin-i89
Description: Dedicated company profiles, storefronts, opening hours, verified badges, and custom branding for Osclass sellers.
Version: 1.0.0
Author: Advanced Agentic Engineer
Author URI: https://osclass.org/
Short Name: business_profile
Plugin update URI:
*/

require_once __DIR__ . "/classes/BusinessProfile.php";

function business_profile_install() {
    $conn = DBConnectionClass::newInstance();
    $db   = $conn->getOsclassDb();
    $comm = new DBCommandClass($db);

    $comm->query("CREATE TABLE IF NOT EXISTS " . DB_TABLE_PREFIX . "t_business_profile (
        fk_i_user_id INT UNSIGNED NOT NULL,
        s_slug VARCHAR(100) NOT NULL,
        s_company_name VARCHAR(150) NOT NULL,
        s_tagline VARCHAR(255) NULL,
        s_bio TEXT NULL,
        s_logo_url VARCHAR(255) NULL,
        s_banner_url VARCHAR(255) NULL,
        s_phone VARCHAR(50) NULL,
        s_whatsapp VARCHAR(50) NULL,
        s_website VARCHAR(255) NULL,
        s_email VARCHAR(100) NULL,
        s_address VARCHAR(255) NULL,
        d_coord_lat DECIMAL(10, 8) NULL,
        d_coord_long DECIMAL(11, 8) NULL,
        s_opening_hours TEXT NULL,
        s_social_links TEXT NULL,
        s_attributes TEXT NULL,
        b_enabled TINYINT(1) NOT NULL DEFAULT 1,
        b_verified TINYINT(1) NOT NULL DEFAULT 0,
        dt_created_at DATETIME NOT NULL,
        dt_updated_at DATETIME NOT NULL,
        PRIMARY KEY (fk_i_user_id),
        UNIQUE KEY uq_bp_slug (s_slug),
        KEY idx_bp_verified (b_verified)
    ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
}

function business_profile_uninstall() {
    $conn = DBConnectionClass::newInstance();
    $db   = $conn->getOsclassDb();
    $comm = new DBCommandClass($db);
    $comm->query("DROP TABLE IF EXISTS " . DB_TABLE_PREFIX . "t_business_profile");
}

osc_register_plugin(osc_plugin_path(__FILE__), "business_profile_install");
osc_add_hook(osc_plugin_path(__FILE__) . "_uninstall", "business_profile_uninstall");
