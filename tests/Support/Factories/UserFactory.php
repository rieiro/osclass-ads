<?php
declare(strict_types=1);

namespace Tests\Support\Factories;

use DAO;
use Tests\Support\FactoryClock;

final class UserFactory
{
    private static int $seq = 1000;

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    public static function create(array $overrides = []): array
    {
        self::$seq++;
        $now = FactoryClock::format();
        $data = array_merge([
            "dt_reg_date" => $now,
            "dt_mod_date" => $now,
            "s_name" => "Test User " . self::$seq,
            "s_username" => "user_" . self::$seq,
            "s_password" => "\$2y\$04\$abcdefghijklmnopqrstuuW4a/O1aB2c3D4e5F6g7H8i9J0k1L2m3",
            "s_secret" => "sec_" . self::$seq,
            "s_email" => "user" . self::$seq . "@example.test",
            "s_phone_mobile" => "11987654321",
            "b_enabled" => 1,
            "b_active" => 1,
            "b_company" => 0,
            "i_items" => 0,
            "i_comments" => 0,
        ], $overrides);

        $dao = new DAO();
        $dao->dao->insert(DB_TABLE_PREFIX . "t_user", $data);
        $id = (int) $dao->dao->insertedId();
        $data["pk_i_id"] = $id;
        return $data;
    }
}
