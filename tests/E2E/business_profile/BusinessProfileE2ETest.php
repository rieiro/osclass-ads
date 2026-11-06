<?php
declare(strict_types=1);

namespace Tests\E2E\business_profile;

use BusinessProfile;
use Tests\Support\E2ETestCase;
use Tests\Support\Factories\ItemFactory;
use Tests\Support\Factories\UserFactory;

final class BusinessProfileE2ETest extends E2ETestCase
{
    public function testStorefrontPageLoadsWithBusinessDataAndInventory(): void
    {
        $user = UserFactory::create(["s_name" => "Alpha Motors"]);
        $bp = BusinessProfile::newInstance();
        $bp->saveProfile((int) $user["pk_i_id"], [
            "s_slug" => "alpha-motors",
            "s_company_name" => "Alpha Motors Official",
            "s_tagline" => "Premium Vehicles",
            "s_whatsapp" => "11977776666",
            "b_verified" => 1,
        ]);

        ItemFactory::create([
            "fk_i_user_id" => $user["pk_i_id"],
            "s_title" => "Alpha Sedan 2026",
            "s_city" => "Sao Paulo",
        ]);

        $res = $this->httpRequest("/index.php?page=business&slug=alpha-motors");
        $this->assertSame(200, $res["status"]);
        $this->assertStringContainsString("Alpha Motors Official", $res["body"]);
        $this->assertStringContainsString("Premium Vehicles", $res["body"]);
        $this->assertStringContainsString("Alpha Sedan 2026", $res["body"]);
        $this->assertSnapshot("business_profile", "storefront_status", "HTTP_200_INVENTORY_MATCH");
    }

    public function testUnknownBusinessSlugReturns404(): void
    {
        $res = $this->httpRequest("/index.php?page=business&slug=nonexistent-company-slug");
        $this->assertContains($res["status"], [301, 302, 404]);
        $this->assertSnapshot("business_profile", "unknown_slug", "handled_404_or_redirect");
    }
}
