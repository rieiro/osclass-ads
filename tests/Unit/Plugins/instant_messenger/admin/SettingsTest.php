<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\instant_messenger\admin;

use Tests\Support\DatabaseTestCase;

final class SettingsTest extends DatabaseTestCase
{
    public function testAdminSettingsTemplateExists(): void
    {
        $path = ABS_PATH . "src/Plugins/instant_messenger/admin/settings.php";
        $this->assertFileExists($path);
        $this->assertStringContainsString("instant_messenger", (string) file_get_contents($path));
    }
}
