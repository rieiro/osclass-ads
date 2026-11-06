<?php
declare(strict_types=1);

namespace Tests\Support;

final class TestMailTrap
{
    /** @var array<int, array<string, mixed>> */
    public static array $messages = [];

    public static function reset(): void
    {
        self::$messages = [];
    }

    /**
     * @param array<string, mixed> $mail
     * @return array<string, mixed>
     */
    public static function intercept(array $mail): array
    {
        self::$messages[] = $mail;
        // Clear recipients so Osclass PHPMailer does not attempt network send
        $mail["to"] = [];
        return $mail;
    }
}
