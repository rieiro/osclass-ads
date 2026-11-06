<?php
declare(strict_types=1);

namespace Tests\E2E\olx_bump;

use Tests\Support\E2ETestCase;

final class OlxBumpE2ETest extends E2ETestCase
{
    public function testHomeRendersPromotionCardStyles(): void
    {
        $res = $this->httpRequest("/");
        $this->assertSame(200, $res["status"]);
        $this->assertStringContainsString("olx-highlighted-card", $res["body"]);
        $this->assertSnapshot("olx_bump", "highlight_css", "olx-highlighted-card:present");
    }
}
