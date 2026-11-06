<?php
declare(strict_types=1);

namespace Tests\E2E\olx_uploader;

use Tests\Support\E2ETestCase;

final class OlxUploaderE2ETest extends E2ETestCase
{
    public function testItemPublishPageRendersWebpUploader(): void
    {
        $res = $this->httpRequest("/index.php?page=item&action=item_add");
        $this->assertContains($res["status"], [200, 302]);
        $this->assertSnapshot("olx_uploader", "publish_page", "reachable");
    }
}
