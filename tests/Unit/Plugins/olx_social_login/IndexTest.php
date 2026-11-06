<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\olx_social_login;

use Tests\Support\DatabaseTestCase;

final class IndexTest extends DatabaseTestCase
{
    public function testAuthenticateUserCreatesAndLinksSocialAccount(): void
    {
        $userId = olx_social_login_authenticate_user("google", [
            "uid" => "google_uid_999",
            "email" => "social999@example.test",
            "name" => "Social Tester",
            "picture" => "",
        ]);

        $this->assertGreaterThan(0, (int) $userId);
        $res = $this->dao->dao->query("SELECT * FROM " . DB_TABLE_PREFIX . "t_user_social WHERE s_provider_uid = 'google_uid_999'");
        $this->assertNotFalse($res);
        $row = $res->row();
        $this->assertSame((int) $userId, (int) $row["fk_i_user_id"]);
    }
}
