<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\olx_audit_log;

use Tests\Support\DatabaseTestCase;

final class AdminTest extends DatabaseTestCase
{
    public function testAdminAuditViewerFileExists(): void
    {
        $path = ABS_PATH . "src/Plugins/olx_audit_log/admin.php";
        $this->assertFileExists($path);
        $this->assertStringContainsString("t_audit_log", (string) file_get_contents($path));
    }
}
