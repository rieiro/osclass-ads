<?php
declare(strict_types=1);

date_default_timezone_set("UTC");
putenv("APP_ENV=testing");
putenv("DB_DATABASE=osclass_testing");

if (getenv("APP_ENV") !== "testing" || getenv("DB_DATABASE") !== "osclass_testing") {
    fwrite(STDERR, "CRITICAL ERROR: Tests attempted outside isolated testing environment!\n");
    exit(1);
}

spl_autoload_register(static function (string $class): void {
    $prefix = "Tests\\";
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $rel = substr($class, strlen($prefix));
    $file = __DIR__ . "/" . str_replace("\\", "/", $rel) . ".php";
    if (is_file($file)) {
        require_once $file;
    }
});

$_SERVER["HTTP_HOST"] = "192.168.122.222";
$_SERVER["SERVER_NAME"] = "192.168.122.222";
$_SERVER["SERVER_PORT"] = "443";
$_SERVER["HTTPS"] = "on";
$_SERVER["REQUEST_URI"] = "/";
$_SERVER["REMOTE_ADDR"] = "127.0.0.1";
$_SERVER["REQUEST_METHOD"] = "GET";
$_SERVER["HTTP_USER_AGENT"] = "PHPUnit-FactoryTest/1.0";

if (!defined("ABS_PATH")) {
    define("ABS_PATH", dirname(__DIR__) . "/");
}
if (!defined("OC_TEST_MODE")) {
    define("OC_TEST_MODE", true);
}

require_once ABS_PATH . "oc-load.php";

if (!defined("DB_NAME") || DB_NAME !== "osclass_testing") {
    fwrite(STDERR, "CRITICAL ERROR: DB_NAME is not osclass_testing!\n");
    exit(1);
}

if (function_exists("osc_add_filter")) {
    osc_add_filter("pre_send_mail", [\Tests\Support\TestMailTrap::class, "intercept"]);
}

foreach (glob(ABS_PATH . "oc-content/plugins/*/index.php") ?: [] as $pluginEntry) {
    require_once $pluginEntry;
}
