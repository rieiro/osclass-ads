<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\instant_messenger;

use Tests\Support\DatabaseTestCase;

final class ItemChatTest extends DatabaseTestCase
{
    public function testItemChatViewTemplateExists(): void
    {
        $path = ABS_PATH . "src/Plugins/instant_messenger/item_chat.php";
        $this->assertFileExists($path);
        $this->assertStringContainsString("im-item-chat-btn", (string) file_get_contents($path));
    }

    public function testItemChatModalDoesNotAllowFileAttachments(): void
    {
        $source = (string) file_get_contents(ABS_PATH . "src/Plugins/instant_messenger/item_chat.php");
        $this->assertStringNotContainsString('type="file"', $source);
        $this->assertStringNotContainsString('name="attachment"', $source);
    }
}
