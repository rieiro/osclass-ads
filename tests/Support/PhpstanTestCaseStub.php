<?php
declare(strict_types=1);

namespace PHPUnit\Framework;

if (!class_exists(TestCase::class, false)) {
    abstract class TestCase
    {
        protected function setUp(): void {}
        protected function tearDown(): void {}
        public static function assertTrue(mixed $condition, string $message = ""): void {}
        public static function assertFalse(mixed $condition, string $message = ""): void {}
        public static function assertSame(mixed $expected, mixed $actual, string $message = ""): void {}
        public static function assertEquals(mixed $expected, mixed $actual, string $message = ""): void {}
        public static function assertNotEmpty(mixed $actual, string $message = ""): void {}
        public static function assertEmpty(mixed $actual, string $message = ""): void {}
        public static function assertNotNull(mixed $actual, string $message = ""): void {}
        public static function assertNull(mixed $actual, string $message = ""): void {}
        public static function assertNotFalse(mixed $actual, string $message = ""): void {}
        public static function assertGreaterThan(mixed $expected, mixed $actual, string $message = ""): void {}
        public static function assertIsArray(mixed $actual, string $message = ""): void {}
        public static function assertArrayHasKey(mixed $key, mixed $array, string $message = ""): void {}
        public static function assertContains(mixed $needle, mixed $haystack, string $message = ""): void {}
        public static function assertStringStartsWith(string $prefix, string $string, string $message = ""): void {}
        public static function assertMatchesRegularExpression(string $pattern, string $string, string $message = ""): void {}
        public static function assertStringContainsString(string $needle, string $haystack, string $message = ""): void {}
        public static function assertStringNotContainsString(string $needle, string $haystack, string $message = ""): void {}
        public static function assertFileExists(string $filename, string $message = ""): void {}
    }
}
