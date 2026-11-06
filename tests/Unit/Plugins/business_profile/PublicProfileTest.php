<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\business_profile;

use BusinessProfile;
use Params;
use Tests\Support\DatabaseTestCase;
use Tests\Support\Factories\ItemFactory;
use Tests\Support\Factories\UserFactory;
use View;

final class PublicProfileTest extends DatabaseTestCase
{
    public function testItemDetailHookRendersBusinessBadge(): void
    {
        $seller = UserFactory::create(["s_name" => "Auto Store"]);
        $bp = BusinessProfile::newInstance();
        $bp->saveProfile((int) $seller["pk_i_id"], [
            "s_slug" => "auto-store-motors",
            "s_company_name" => "Auto Store Motors",
            "s_whatsapp" => "11988887777",
            "b_verified" => 1,
        ]);

        $item = ItemFactory::create(["fk_i_user_id" => $seller["pk_i_id"]]);
        View::newInstance()->_exportVariableToView("item", $item);

        $this->assertTrue(function_exists("business_profile_item_detail"));
        $html = $this->captureOutput(fn() => business_profile_item_detail());

        $this->assertStringContainsString("Auto Store Motors", $html);
        $this->assertStringContainsString("auto-store-motors", $html);
        $this->assertStringContainsString("Verified Business", $html);
        $this->assertStringContainsString("wa.me/5511988887777", $html);
    }

    public function testPublicProfileViewTemplateExists(): void
    {
        $path = ABS_PATH . "src/Plugins/business_profile/public_profile.php";
        $this->assertFileExists($path);
        $this->assertStringContainsString("bp-storefront", (string) file_get_contents($path));
    }
}
