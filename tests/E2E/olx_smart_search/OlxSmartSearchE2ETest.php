<?php
declare(strict_types=1);

namespace Tests\E2E\olx_smart_search;

use Tests\Support\E2ETestCase;

final class OlxSmartSearchE2ETest extends E2ETestCase
{
    public function testAutocompleteAjaxEndpointReturnsJsonStructure(): void
    {
        $res = $this->httpRequest("/index.php?page=smart_search_ajax&q=carro");
        $this->assertSame(200, $res["status"]);
        $json = json_decode($res["body"], true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey("items", $json);
        $this->assertSnapshot("olx_smart_search", "ajax_keys", implode(",", array_keys($json)));
    }
}
