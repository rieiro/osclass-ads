<?php
declare(strict_types=1);

namespace Tests\E2E\olx_analytics;

use Tests\Support\E2ETestCase;

final class OlxAnalyticsE2ETest extends E2ETestCase
{
    public function testAdminLoginEndpointIsReachable(): void
    {
        $res = $this->httpRequest("/oc-admin/index.php?page=login");
        $this->assertSame(200, $res["status"]);
        $this->assertSnapshot("olx_analytics", "admin_login_status", "HTTP_200");
    }
}
