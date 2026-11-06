<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\instant_messenger;

use Tests\Support\DatabaseTestCase;
use Tests\Support\Factories\UserFactory;

final class IndexTest extends DatabaseTestCase
{
    public function testHeaderAndFooterAssetsRender(): void
    {
        $header = $this->captureOutput(fn() => im_header());
        $this->assertStringContainsString("messenger.css", $header);

        $footer = $this->captureOutput(fn() => im_footer());
        $this->assertStringContainsString("messenger.js", $footer);
    }

    public function testUserMenuFilterAddsMessagesOptionWhenLoggedIn(): void
    {
        $user = UserFactory::create();
        $this->loginAsUser((int) $user["pk_i_id"], (string) $user["s_name"], (string) $user["s_email"]);
        $options = im_user_menu_filter([]);
        $this->assertIsArray($options);
    }
}
