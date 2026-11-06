<?php
declare(strict_types=1);

namespace Tests\Support;

use DateTimeImmutable;

final class FactoryClock
{
    private static ?ClockInterface $clock = null;

    public static function get(): ClockInterface
    {
        if (self::$clock === null) {
            self::$clock = new SystemClock();
        }
        return self::$clock;
    }

    public static function set(ClockInterface $clock): void
    {
        self::$clock = $clock;
    }

    public static function reset(): void
    {
        self::$clock = new FrozenClock("2026-11-06 12:00:00");
    }

    public static function now(): DateTimeImmutable
    {
        return self::get()->now();
    }

    public static function timestamp(): int
    {
        return self::get()->timestamp();
    }

    public static function format(string $format = "Y-m-d H:i:s"): string
    {
        return self::get()->format($format);
    }
}
