<?php
declare(strict_types=1);

namespace Tests\Support;

use RuntimeException;

final class TestTerminationException extends RuntimeException
{
    public string $output;
    public int $statusCode;

    public function __construct(string $output = "", int $statusCode = 200)
    {
        parent::__construct("Terminated in test mode: " . $output, $statusCode);
        $this->output = $output;
        $this->statusCode = $statusCode;
    }
}
