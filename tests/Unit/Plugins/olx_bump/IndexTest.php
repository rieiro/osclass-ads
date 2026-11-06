<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\olx_bump;

use Tests\Support\DatabaseTestCase;
use Tests\Support\Factories\ItemFactory;

final class IndexTest extends DatabaseTestCase
{
    public function testIsItemHighlightedReturnsTrueWhenActivePromotionExists(): void
    {
        $item = ItemFactory::create();
        $itemId = (int) $item["pk_i_id"];

        $this->assertFalse(olx_is_item_highlighted($itemId));

        $this->dao->dao->insert(DB_TABLE_PREFIX . "t_olx_promotions", [
            "fk_i_item_id" => $itemId,
            "b_highlighted" => 1,
            "dt_highlight_until" => "2099-12-31 23:59:59",
            "i_bumps_count" => 1,
        ]);

        $this->assertTrue(olx_is_item_highlighted($itemId));
    }

    public function testBumpHeaderOutputsStyles(): void
    {
        $html = $this->captureOutput(fn() => olx_bump_header());
        $this->assertStringContainsString("olx-highlighted-card", $html);
    }
}
