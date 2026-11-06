<?php
declare(strict_types=1);

namespace Tests\Support;

use DBConnectionClass;
use mysqli;
use PHPUnit\Framework\TestCase;

abstract class E2ETestCase extends TestCase
{
    protected mysqli $db;
    protected string $baseUrl = "https://127.0.0.1";
    protected string $hostHeader = "192.168.122.222";

    /** @var array<int, string> */
    private array $mutableTables = [
        "oc_t_business_profile",
        "oc_t_audit_log",
        "oc_t_im_messages",
        "oc_t_im_threads",
        "oc_t_im_blocks",
        "oc_t_olx_chat_message",
        "oc_t_olx_chat_thread",
        "oc_t_olx_seller_review",
        "oc_t_olx_push_subscriptions",
        "oc_t_olx_orders",
        "oc_t_olx_promotions",
        "oc_t_olx_stores",
        "oc_t_user_social",
        "oc_t_latest_searches",
        "oc_t_item_description",
        "oc_t_item_location",
        "oc_t_item_stats",
        "oc_t_item_resource",
        "oc_t_item_meta",
        "oc_t_item_comment",
        "oc_t_item",
        "oc_t_user_description",
        "oc_t_user",
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = DBConnectionClass::newInstance()->getOsclassDb();
        $this->cleanMutableTables();
    }

    protected function tearDown(): void
    {
        if (isset($this->db)) {
            $this->cleanMutableTables();
        }
        parent::tearDown();
    }

    protected function cleanMutableTables(): void
    {
        $this->db->query("SET FOREIGN_KEY_CHECKS = 0");
        foreach ($this->mutableTables as $table) {
            $this->db->query("DELETE FROM {$table}");
        }
        $this->db->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    /**
     * @param array<string, mixed> $postFields
     * @param array<int, string> $extraHeaders
     * @return array{status: int, body: string, headers: string}
     */
    protected function httpRequest(string $path, string $method = "GET", array $postFields = [], array $extraHeaders = []): array
    {
        $url = rtrim($this->baseUrl, "/") . "/" . ltrim($path, "/");
        $ch = curl_init($url);
        $headers = array_merge([
            "Host: " . $this->hostHeader,
            "X-Factory-Env: testing",
            "User-Agent: OsclassFactoryE2E/1.0",
        ], $extraHeaders);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 10,
        ]);

        if (!empty($postFields)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
        }

        $raw = (string) curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        return [
            "status" => $status,
            "headers" => substr($raw, 0, $headerSize),
            "body" => substr($raw, $headerSize),
        ];
    }

    protected function assertSnapshot(string $plugin, string $snapshotName, string $content): void
    {
        $dir = dirname(__DIR__) . "/E2E/{$plugin}/snapshots";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $file = "{$dir}/{$snapshotName}.snap";
        if (!file_exists($file)) {
            file_put_contents($file, $content);
        }
        $expected = (string) file_get_contents($file);
        $this->assertSame($expected, $content, "Snapshot mismatch for {$plugin}/{$snapshotName}.snap");
    }
}
