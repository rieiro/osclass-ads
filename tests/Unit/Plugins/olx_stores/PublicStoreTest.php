<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\olx_stores;

use Tests\Support\DatabaseTestCase;

final class PublicStoreTest extends DatabaseTestCase
{
    public function testPublicStoreTemplateExists(): void
    {
        $path = ABS_PATH . "src/Plugins/olx_stores/public_store.php";
        $this->assertFileExists($path);
        $this->assertStringContainsString("Available Inventory", (string) file_get_contents($path));
    }
}
