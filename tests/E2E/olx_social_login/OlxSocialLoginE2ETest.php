<?php
declare(strict_types=1);

namespace Tests\E2E\olx_social_login;

use Tests\Support\E2ETestCase;

final class OlxSocialLoginE2ETest extends E2ETestCase
{
    public function testLoginPageRendersSocialLoginButtons(): void
    {
        $res = $this->httpRequest("/index.php?page=login");
        $this->assertSame(200, $res["status"]);
        $this->assertStringContainsString("olx-social-btn", $res["body"]);
        $this->assertSnapshot("olx_social_login", "login_buttons", "olx-social-btn:present");
    }
}
