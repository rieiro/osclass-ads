<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\olx_geosearch;

use Rewrite;
use Tests\Support\DatabaseTestCase;

final class IndexTest extends DatabaseTestCase
{
    public function testGeosearchHeaderAndSidebarRender(): void
    {
        Rewrite::newInstance()->set_location("search");
        $header = $this->captureOutput(fn() => olx_geosearch_header());
        $this->assertStringContainsString("olx-geo-box", $header);

        $sidebar = $this->captureOutput(fn() => olx_geosearch_sidebar());
        $this->assertStringContainsString("olx_radius", $sidebar);
    }
}
