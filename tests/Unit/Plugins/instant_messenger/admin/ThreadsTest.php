<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\instant_messenger\admin;

use Tests\Support\DatabaseTestCase;

final class ThreadsTest extends DatabaseTestCase
{
    public function testAdminThreadsTemplateExists(): void
    {
        $path = ABS_PATH . "src/Plugins/instant_messenger/admin/threads.php";
        $this->assertFileExists($path);
        $this->assertStringContainsString("t_im_threads", (string) file_get_contents($path));
    }
}
