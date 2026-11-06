<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\olx_audit_log;

use Tests\Support\DatabaseTestCase;

final class IndexTest extends DatabaseTestCase
{
    public function testSanitizePayloadMasksPasswordsAndTokens(): void
    {
        $input = [
            "s_email" => "user@example.test",
            "s_password" => "Secret123!",
            "csrf_token" => "tok_abc",
        ];
        $clean = olx_audit_sanitize_payload($input);
        $this->assertSame("user@example.test", $clean["s_email"]);
        $this->assertSame("[REDACTED]", $clean["s_password"]);
        $this->assertSame("[REDACTED]", $clean["csrf_token"]);
    }

    public function testAuditLogRecordInsertsRowIntoDatabase(): void
    {
        olx_audit_log_record("unit_test_event", "item", "99", ["note" => "test"], "success");
        $res = $this->dao->dao->query("SELECT * FROM " . DB_TABLE_PREFIX . "t_audit_log WHERE s_action = 'unit_test_event'");
        $this->assertNotFalse($res);
        $row = $res->row();
        $this->assertNotEmpty($row);
        $this->assertSame("item", $row["s_target_type"]);
        $this->assertSame("99", (string) $row["s_target_id"]);
    }
}
