<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\business_profile;

use BusinessProfile;
use Tests\Support\DatabaseTestCase;

final class AdminTest extends DatabaseTestCase
{
    public function testAdminMenuHookRegistersMenuItem(): void
    {
        $this->assertTrue(function_exists("business_profile_admin_menu"));
    }

    public function testAdminModerationTemplateExists(): void
    {
        $path = ABS_PATH . "src/Plugins/business_profile/admin.php";
        $this->assertFileExists($path);
        $this->assertStringContainsString("t_business_profile", (string) file_get_contents($path));
    }
}
