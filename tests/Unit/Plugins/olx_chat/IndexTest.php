<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\olx_chat;

use Tests\Support\DatabaseTestCase;

final class IndexTest extends DatabaseTestCase
{
    public function testChatHeaderRendersStyles(): void
    {
        $html = $this->captureOutput(fn() => olx_chat_header());
        $this->assertStringContainsString("olx-chat-action-btn", $html);
    }
}
