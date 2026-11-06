<?php
declare(strict_types=1);

namespace Tests\E2E\olx_payments;

use Tests\Support\E2ETestCase;

final class OlxPaymentsE2ETest extends E2ETestCase
{
    public function testWebhookRejectsInvalidSignatureWith401(): void
    {
        $res = $this->httpRequest("/index.php?action=olx_payment_webhook", "POST", ["order_id" => 1]);
        $this->assertContains($res["status"], [400, 401, 403, 405]);
        $this->assertSnapshot("olx_payments", "webhook_unauthorized", "rejected");
    }
}
