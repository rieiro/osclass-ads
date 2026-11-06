<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\olx_chat;

use Tests\Support\DatabaseTestCase;

final class UserChatTest extends DatabaseTestCase
{
    public function testUserChatTemplateExists(): void
    {
        $path = ABS_PATH . "src/Plugins/olx_chat/user_chat.php";
        $this->assertFileExists($path);
        $this->assertStringContainsString("t_olx_chat_thread", (string) file_get_contents($path));
    }
}
