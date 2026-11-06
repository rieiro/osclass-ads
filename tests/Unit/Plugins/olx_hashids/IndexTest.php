<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\olx_hashids;

use OlxHashids;
use Params;
use Tests\Support\DatabaseTestCase;

final class IndexTest extends DatabaseTestCase
{
    public function testEncodeAndDecodeRoundtripIsDeterministic(): void
    {
        foreach ([1, 16, 42, 99999] as $id) {
            $hash = OlxHashids::encode($id);
            $this->assertSame(7, strlen($hash));
            $this->assertSame($id, OlxHashids::decode($hash));
        }
    }

    public function testDecodeRequestTranslatesHashParamToNumericId(): void
    {
        $hash = OlxHashids::encode(16);
        $this->setRequestParams(["id" => $hash], "GET");
        olx_hashids_decode_request();
        $this->assertSame("16", (string) Params::getParam("id"));
    }
}
