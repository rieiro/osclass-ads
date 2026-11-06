<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\olx_stores;

use Tests\Support\DatabaseTestCase;

final class ManageStoreTest extends DatabaseTestCase
{
    public function testManageStoreTemplateExists(): void
    {
        $path = ABS_PATH . "src/Plugins/olx_stores/manage_store.php";
        $this->assertFileExists($path);
        $this->assertStringContainsString("t_olx_stores", (string) file_get_contents($path));
    }
}
