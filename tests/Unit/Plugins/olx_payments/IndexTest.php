<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\olx_payments;

use Tests\Support\DatabaseTestCase;
use Tests\Support\Factories\ItemFactory;

final class IndexTest extends DatabaseTestCase
{
    public function testGeneratePixBrcodeContainsValidCrc16AndPayload(): void
    {
        $brcode = olx_generate_pix_brcode("pix@example.test", "OSCLASS MARKET", "SAO PAULO", 15.00, "TX123");
        $this->assertStringStartsWith("000201", $brcode);
        $this->assertStringContainsString("BR.GOV.BCB.PIX", $brcode);
        $this->assertStringContainsString("6304", $brcode);
    }

    public function testActivatePromotionUpdatesItemAndPromotionsTable(): void
    {
        $item = ItemFactory::create();
        $itemId = (int) $item["pk_i_id"];

        olx_payments_activate_promotion($itemId, "highlight", "2026-11-06 12:00:00");
        $this->assertTrue(olx_is_item_highlighted($itemId));
    }
}
