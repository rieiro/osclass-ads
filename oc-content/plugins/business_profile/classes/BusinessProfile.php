<?php
declare(strict_types=1);

use Tests\Support\ClockInterface;
use Tests\Support\FactoryClock;

class BusinessProfile extends DAO
{
    private static ?self $instance = null;

    public static function newInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        parent::__construct();
        $this->setTableName("t_business_profile");
        $this->setPrimaryKey("fk_i_user_id");
        $this->setFields([
            "fk_i_user_id", "s_slug", "s_company_name", "s_tagline", "s_bio",
            "s_logo_url", "s_banner_url", "s_phone", "s_whatsapp", "s_website",
            "s_email", "s_address", "d_coord_lat", "d_coord_long", "s_opening_hours",
            "s_social_links", "s_attributes", "b_enabled", "b_verified",
            "dt_created_at", "dt_updated_at"
        ]);
    }

    /**
     * @param int $userId
     * @param array<string, mixed> $data
     * @return int
     */
    public function saveProfile(int $userId, array $data): int
    {
        $now = class_exists(FactoryClock::class) ? FactoryClock::format() : date("Y-m-d H:i:s");
        $existing = $this->findByUserId($userId);

        $record = array_merge([
            "s_slug" => "store-" . $userId,
            "s_company_name" => "Company " . $userId,
            "b_enabled" => 1,
            "dt_updated_at" => $now,
        ], $data);

        if ($existing) {
            $this->dao->update($this->getTableName(), $record, ["fk_i_user_id" => $userId]);
            return $userId;
        }

        $record["fk_i_user_id"] = $userId;
        $record["b_verified"] = $record["b_verified"] ?? 0;
        $record["dt_created_at"] = $now;

        $this->dao->insert($this->getTableName(), $record);
        return $userId;
    }

    /**
     * @param int $userId
     * @return array<string, mixed>|false
     */
    public function findByUserId(int $userId): array|false
    {
        $this->dao->select();
        $this->dao->from($this->getTableName());
        $this->dao->where("fk_i_user_id", $userId);
        $res = $this->dao->get();
        if ($res && $res->numRows() > 0) {
            return $res->row();
        }
        return false;
    }

    /**
     * @param string $slug
     * @return array<string, mixed>|false
     */
    public function findBySlug(string $slug): array|false
    {
        $this->dao->select();
        $this->dao->from($this->getTableName());
        $this->dao->where("s_slug", $slug);
        $res = $this->dao->get();
        if ($res && $res->numRows() > 0) {
            return $res->row();
        }
        return false;
    }

    public function isVerified(int $userId): bool
    {
        $profile = $this->findByUserId($userId);
        return $profile !== false && (int) ($profile["b_verified"] ?? 0) === 1;
    }

    public function setVerified(int $userId, bool $verified): bool
    {
        $now = class_exists(FactoryClock::class) ? FactoryClock::format() : date("Y-m-d H:i:s");
        return (bool) $this->dao->update(
            $this->getTableName(),
            ["b_verified" => $verified ? 1 : 0, "dt_updated_at" => $now],
            ["fk_i_user_id" => $userId]
        );
    }

    /**
     * @param array<string, mixed>|string $openingHours
     * @param ClockInterface|null $clock
     * @return bool
     */
    public static function calculateIsOpenNow(array|string $openingHours, ?ClockInterface $clock = null): bool
    {
        if (is_string($openingHours)) {
            $openingHours = (array) json_decode($openingHours, true);
        }
        if (empty($openingHours)) {
            return false;
        }

        $now = $clock ? $clock->now() : new DateTimeImmutable("now", new DateTimeZone("UTC"));
        $dayKey = strtolower($now->format("D")); // "mon", "tue", ...
        $daySchedule = $openingHours[$dayKey] ?? null;

        if (!$daySchedule || !empty($daySchedule["closed"])) {
            return false;
        }

        $openTime = $daySchedule["open"] ?? null;
        $closeTime = $daySchedule["close"] ?? null;
        if (!$openTime || !$closeTime) {
            return false;
        }

        $currentTime = $now->format("H:i");
        return $currentTime >= $openTime && $currentTime <= $closeTime;
    }
}
