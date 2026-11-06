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

    public function testCsvExportUsesResultArrayAndDoesNotInfiniteLoopOnRowMethod(): void
    {
        $path = ABS_PATH . "src/Plugins/olx_smart_search/admin_queries.php";
        $source = (string) file_get_contents($path);

        $this->assertStringNotContainsString('while ($row = $res->row())', $source);
        $this->assertStringContainsString('$res->result()', $source);
    }

    public function testTruncateLogButtonIsRemoved(): void
    {
        $path = ABS_PATH . "src/Plugins/olx_smart_search/admin_queries.php";
        $source = (string) file_get_contents($path);

        $this->assertStringNotContainsString("Truncate Log", $source);
        $this->assertStringNotContainsString("subaction=purge_all", $source);
    }

    public function testTableIconsAndButtonsStyling(): void
    {
        $path = ABS_PATH . "src/Plugins/olx_smart_search/admin_queries.php";
        $source = (string) file_get_contents($path);

        $this->assertStringNotContainsString("fa-external-link-alt", $source);
        $this->assertStringNotContainsString("sq-btn-del", $source);
        $this->assertMatchesRegularExpression('/class=["\']sq-term-badge["\']>\s*<\?php echo osc_esc_html/', $source);
    }
}
