<?php
declare(strict_types=1);

namespace Tests\E2E\instant_messenger;

use Tests\Support\E2ETestCase;

final class InstantMessengerE2ETest extends E2ETestCase
{
    public function testHomeLoadsInstantMessengerAssets(): void
    {
        $res = $this->httpRequest("/");
        $this->assertSame(200, $res["status"]);
        $this->assertStringContainsString("messenger.css", $res["body"]);
        $this->assertSnapshot("instant_messenger", "asset_link", "messenger.css:present");
    }
}
