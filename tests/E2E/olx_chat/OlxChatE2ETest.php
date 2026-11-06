<?php
declare(strict_types=1);

namespace Tests\E2E\olx_chat;

use Tests\Support\E2ETestCase;

final class OlxChatE2ETest extends E2ETestCase
{
    public function testChatNavigationTabIsRenderedInAppLayout(): void
    {
        $res = $this->httpRequest("/");
        $this->assertSame(200, $res["status"]);
        $this->assertStringContainsString("<span>Chat</span>", $res["body"]);
        $this->assertSnapshot("olx_chat", "chat_nav", "chat_tab:present");
    }
}
