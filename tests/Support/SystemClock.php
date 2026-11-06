<?php
declare(strict_types=1);

namespace Tests\Support;

use DateTimeImmutable;
use DateTimeZone;

final class SystemClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable("now", new DateTimeZone("UTC"));
    }

    public function timestamp(): int
    {
        return $this->now()->getTimestamp();
    }

    public function format(string $format = "Y-m-d H:i:s"): string
    {
        return $this->now()->format($format);
    }
}
