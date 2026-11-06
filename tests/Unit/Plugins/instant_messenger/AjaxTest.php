<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\instant_messenger;

use Tests\Support\DatabaseTestCase;

final class AjaxTest extends DatabaseTestCase
{
    public function testAjaxHandlerScriptExistsAndDefinesJsonFlow(): void
    {
        $path = ABS_PATH . "src/Plugins/instant_messenger/ajax.php";
        $this->assertFileExists($path);
        $source = (string) file_get_contents($path);
        $this->assertStringContainsString("im_get_messages", $source);
        $this->assertStringContainsString("im_send_message", $source);
    }

    public function testAjaxSupportsNonBlockingResponseAndOptimisticClient(): void
    {
        $ajaxSource = (string) file_get_contents(ABS_PATH . "src/Plugins/instant_messenger/ajax.php");
        $this->assertStringContainsString("fastcgi_finish_request", $ajaxSource);

        $jsSource = (string) file_get_contents(ABS_PATH . "src/Plugins/instant_messenger/assets/js/messenger.js");
        $this->assertStringContainsString("im-msg-pending", $jsSource);
    }
}
