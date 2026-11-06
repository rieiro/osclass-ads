<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\olx_smart_search;

use Tests\Support\DatabaseTestCase;

final class AdminQueriesTest extends DatabaseTestCase
{
    public function testAdminQueriesUsesRollbackSafeDeleteInsteadOfTruncate(): void
    {
        $path = ABS_PATH . "src/Plugins/olx_smart_search/admin_queries.php";
        $source = (string) file_get_contents($path);
        $this->assertStringNotContainsString("TRUNCATE TABLE", $source);
        $this->assertStringContainsString("DELETE FROM %st_latest_searches", $source);
    }
}
