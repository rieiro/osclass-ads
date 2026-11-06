<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\instant_messenger;

use Tests\Support\DatabaseTestCase;

final class GuestChatTest extends DatabaseTestCase
{
    public function testGuestChatViewTemplateExists(): void
    {
        $path = ABS_PATH . "src/Plugins/instant_messenger/guest_chat.php";
        $this->assertFileExists($path);
        $this->assertStringContainsString("getThreadByToken", (string) file_get_contents($path));
    }
}
