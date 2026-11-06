<?php
declare(strict_types=1);

namespace Tests\Support;

use DateTimeImmutable;

interface ClockInterface
{
    public function now(): DateTimeImmutable;
    public function timestamp(): int;
    public function format(string $format = "Y-m-d H:i:s"): string;
}
