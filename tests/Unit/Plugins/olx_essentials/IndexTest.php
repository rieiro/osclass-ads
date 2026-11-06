<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\olx_essentials;

use Rewrite;
use Tests\Support\DatabaseTestCase;

final class IndexTest extends DatabaseTestCase
{
    public function testEssentialsHeaderOutputsWhatsappAndMapStyles(): void
    {
        Rewrite::newInstance()->set_location("item");
        $html = $this->captureOutput(fn() => olx_essentials_header());
        $this->assertStringContainsString("olx-btn-whatsapp", $html);
        $this->assertStringContainsString("leaflet", $html);
    }
}
