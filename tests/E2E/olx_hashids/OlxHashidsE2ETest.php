<?php
declare(strict_types=1);

namespace Tests\E2E\olx_hashids;

use Tests\Support\E2ETestCase;

final class OlxHashidsE2ETest extends E2ETestCase
{
    public function testHomePageRewritesLinksAndReturns200(): void
    {
        $res = $this->httpRequest("/");
        $this->assertSame(200, $res["status"]);
        $this->assertSnapshot("olx_hashids", "home_rewrite", "HTTP_200");
    }
}
