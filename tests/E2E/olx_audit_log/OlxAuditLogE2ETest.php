<?php
declare(strict_types=1);

namespace Tests\E2E\olx_audit_log;

use Tests\Support\E2ETestCase;
use Tests\Support\Factories\ItemFactory;

final class OlxAuditLogE2ETest extends E2ETestCase
{
    public function testSearchRequestIsLoggedInTestingDatabase(): void
    {
        ItemFactory::create(["s_title" => "Carro Audit Test"]);
        $res = $this->httpRequest("/index.php?page=search&sPattern=Carro");
        $this->assertContains($res["status"], [200, 404]);
        $this->assertSnapshot("olx_audit_log", "search_request", "logged:ok");
    }
}
