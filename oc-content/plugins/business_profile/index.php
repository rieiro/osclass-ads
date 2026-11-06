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

// Router for ?page=business&slug={slug}
function business_profile_router() {
    if (Params::getParam("page") === "business" && Params::getParam("slug") !== "") {
        require_once __DIR__ . "/public_profile.php";
        if (!defined("OC_TEST_MODE") || !OC_TEST_MODE) {
            exit;
        }
    }
}

// Injects business badge into item details sidebar
function business_profile_item_detail() {
    $userId = (int)osc_item_user_id();
    if ($userId <= 0) {
        return;
    }

    $bp = BusinessProfile::newInstance();
    $profile = $bp->findByUserId($userId);
    if (!$profile || (int)($profile["b_enabled"] ?? 0) !== 1) {
        return;
    }

    $profileUrl = osc_base_url(true) . "?page=business&slug=" . urlencode((string)$profile["s_slug"]);
    $cleanWa = preg_replace("/[^0-9]/", "", (string)($profile["s_whatsapp"] ?? ""));

    echo '<div class="bp-item-badge" style="margin: 16px 0; padding: 16px; background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 10px;">';
    echo '<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">';
    echo '<span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #475569; letter-spacing: 0.5px;">Seller Profile</span>';
    if ((int)($profile["b_verified"] ?? 0) === 1) {
        echo '<span style="background: #22c55e; color: #fff; font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 2px 7px; border-radius: 12px;">&#10004; Verified Business</span>';
    }
    echo '</div>';
    echo '<h3 style="margin: 0 0 4px 0; font-size: 17px; font-weight: 800; color: #0f172a;">' . osc_esc_html($profile["s_company_name"]) . '</h3>';
    if (!empty($profile["s_tagline"])) {
        echo '<p style="margin: 0 0 12px 0; font-size: 13px; color: #64748b;">' . osc_esc_html($profile["s_tagline"]) . '</p>';
    }
    echo '<div style="display: flex; gap: 8px; margin-top: 10px;">';
    echo '<a href="' . osc_esc_html($profileUrl) . '" style="flex: 1; text-align: center; background: #4f46e5; color: #fff; text-decoration: none; padding: 8px 12px; border-radius: 6px; font-weight: 700; font-size: 13px;">View Storefront &rarr;</a>';
    if (!empty($cleanWa)) {
        echo '<a href="https://wa.me/55' . $cleanWa . '" target="_blank" rel="noopener noreferrer" style="background: #22c55e; color: #fff; text-decoration: none; padding: 8px 12px; border-radius: 6px; font-weight: 700; font-size: 13px;">WhatsApp</a>';
    }
    echo '</div>';
    echo '</div>';
}

osc_register_plugin(osc_plugin_path(__FILE__), "business_profile_install");
osc_add_hook(osc_plugin_path(__FILE__) . "_uninstall", "business_profile_uninstall");
osc_add_hook("init", "business_profile_router");
osc_add_hook("item_detail", "business_profile_item_detail");
