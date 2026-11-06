<?php
declare(strict_types=1);

namespace Tests\Support;

use DateTimeImmutable;
use DateTimeZone;

final class FrozenClock implements ClockInterface
{
    private DateTimeImmutable $current;

    public function __construct(string $isoTime = "2026-11-06 12:00:00")
    {
        $this->current = new DateTimeImmutable($isoTime, new DateTimeZone("UTC"));
    }

    public function now(): DateTimeImmutable
    {
        return $this->current;
    }

    public function timestamp(): int
    {
        return $this->current->getTimestamp();
    }

    public function format(string $format = "Y-m-d H:i:s"): string
    {
        return $this->current->format($format);
    }

    public function advanceSeconds(int $seconds): void
    {
        $sign = $seconds >= 0 ? "+" : "";
        $this->current = $this->current->modify("{$sign}{$seconds} seconds");
    }
}
