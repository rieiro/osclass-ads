<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\olx_stores;

use Tests\Support\DatabaseTestCase;
use Tests\Support\Factories\UserFactory;

final class IndexTest extends DatabaseTestCase
{
    public function testStoreCreationAndLookupInDatabase(): void
    {
        $user = UserFactory::create();
        $this->dao->dao->insert(DB_TABLE_PREFIX . "t_olx_stores", [
            "fk_i_user_id" => $user["pk_i_id"],
            "s_slug" => "unit-test-motors",
            "s_store_name" => "Unit Test Motors",
            "s_tagline" => "Trusted Cars",
            "s_whatsapp" => "11988887777",
            "dt_created_at" => "2026-11-06 12:00:00",
        ]);

        $res = $this->dao->dao->query("SELECT * FROM " . DB_TABLE_PREFIX . "t_olx_stores WHERE s_slug = 'unit-test-motors'");
        $this->assertNotFalse($res);
        $store = $res->row();
        $this->assertSame("Unit Test Motors", $store["s_store_name"]);
    }
}
