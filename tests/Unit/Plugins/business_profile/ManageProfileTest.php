<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\business_profile;

use BusinessProfile;
use Tests\Support\DatabaseTestCase;
use Tests\Support\Factories\UserFactory;

final class ManageProfileTest extends DatabaseTestCase
{
    public function testUserMenuHookRendersBusinessProfileOption(): void
    {
        $user = UserFactory::create();
        $this->loginAsUser((int) $user["pk_i_id"], (string) $user["s_name"], (string) $user["s_email"]);

        $this->assertTrue(function_exists("business_profile_user_menu"));
        $html = $this->captureOutput(fn() => business_profile_user_menu());
        $this->assertStringContainsString("business_profile", $html);
        $this->assertStringContainsString("Business Profile", $html);
    }

    public function testManageProfileTemplateExists(): void
    {
        $path = ABS_PATH . "src/Plugins/business_profile/manage_profile.php";
        $this->assertFileExists($path);
        $this->assertStringContainsString("bp-manage-form", (string) file_get_contents($path));
    }

    public function testManageProfileDoesNotDuplicateThemeHeaderAndFooter(): void
    {
        $path = ABS_PATH . "src/Plugins/business_profile/manage_profile.php";
        $source = (string) file_get_contents($path);
        $this->assertStringNotContainsString("osc_current_web_theme_path('header.php')", $source);
        $this->assertStringNotContainsString("osc_current_web_theme_path('footer.php')", $source);
    }
}
