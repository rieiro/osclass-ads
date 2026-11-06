<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\olx_essentials;

use Tests\Support\DatabaseTestCase;

final class AdminTest extends DatabaseTestCase
{
    public function testEssentialsAdminFileExists(): void
    {
        $path = ABS_PATH . "src/Plugins/olx_essentials/admin.php";
        $this->assertFileExists($path);
        $this->assertStringContainsString("olx_essentials", (string) file_get_contents($path));
    }
}
