<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\olx_social_login;

use Tests\Support\DatabaseTestCase;

final class AdminTest extends DatabaseTestCase
{
    public function testSocialLoginAdminFileExists(): void
    {
        $path = ABS_PATH . "src/Plugins/olx_social_login/admin.php";
        $this->assertFileExists($path);
        $this->assertStringContainsString("olx_social_login", (string) file_get_contents($path));
    }
}
