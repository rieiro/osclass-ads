<?php
declare(strict_types=1);

namespace Tests\E2E\olx_geosearch;

use Tests\Support\E2ETestCase;
use Tests\Support\Factories\ItemFactory;

final class OlxGeosearchE2ETest extends E2ETestCase
{
    public function testSearchPageRendersGeosearchRadiusFilter(): void
    {
        ItemFactory::create(["s_title" => "Geo Search Item"]);
        $res = $this->httpRequest("/index.php?page=search");
        $this->assertContains($res["status"], [200, 404]);
        $this->assertStringContainsString("olx_radius", $res["body"]);
        $this->assertSnapshot("olx_geosearch", "search_sidebar_radius", "olx_radius:present");
    }
}
