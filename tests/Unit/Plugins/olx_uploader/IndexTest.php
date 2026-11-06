<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\olx_uploader;

use Rewrite;
use Tests\Support\DatabaseTestCase;

final class IndexTest extends DatabaseTestCase
{
    public function testUploaderHeaderOutputsDropzoneStyles(): void
    {
        $this->setRequestParams(["page" => "item", "action" => "item_add"], "GET");
        Rewrite::newInstance()->init();
        $html = $this->captureOutput(fn() => olx_uploader_header());
        $this->assertStringContainsString("olx-upload-container", $html);
    }
}
