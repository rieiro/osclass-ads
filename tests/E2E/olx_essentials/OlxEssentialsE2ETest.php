<?php
declare(strict_types=1);

namespace Tests\E2E\olx_essentials;

use Tests\Support\E2ETestCase;

final class OlxEssentialsE2ETest extends E2ETestCase
{
    public function testHomePageLoadsCleanlyWithEssentialsActive(): void
    {
        $res = $this->httpRequest("/");
        $this->assertSame(200, $res["status"]);
        $this->assertSnapshot("olx_essentials", "home_status", "HTTP_200");
    }
}
