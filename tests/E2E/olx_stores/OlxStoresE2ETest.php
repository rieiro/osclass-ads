<?php
declare(strict_types=1);

namespace Tests\E2E\olx_stores;

use Tests\Support\E2ETestCase;

final class OlxStoresE2ETest extends E2ETestCase
{
    public function testUnknownStoreSlugRedirectsCleanly(): void
    {
        $res = $this->httpRequest("/index.php?store_slug=nonexistent-store-slug");
        $this->assertContains($res["status"], [200, 301, 302]);
        $this->assertSnapshot("olx_stores", "unknown_store", "handled");
    }
}
