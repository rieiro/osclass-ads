<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\instant_messenger;

use Tests\Support\DatabaseTestCase;

final class InboxTest extends DatabaseTestCase
{
    public function testInboxViewTemplateExists(): void
    {
        $path = ABS_PATH . "src/Plugins/instant_messenger/inbox.php";
        $this->assertFileExists($path);
        $this->assertStringContainsString("InstantMessenger::newInstance", (string) file_get_contents($path));
    }
}
