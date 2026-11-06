<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\olx_smart_search;

use Tests\Support\DatabaseTestCase;
use Tests\Support\Factories\ItemFactory;

final class IndexTest extends DatabaseTestCase
{
    public function testRemoveAccentsNormalizesPortugueseStrings(): void
    {
        $this->assertSame("caminhao bau", olx_smart_remove_accents("Caminhão Baú"));
        $this->assertSame("imoveis sao paulo", olx_smart_remove_accents("Imóveis São Paulo"));
    }

    public function testDetectCategoryMatchesCarsForCarroToken(): void
    {
        ItemFactory::create([
            "fk_i_category_id" => 31,
            "s_title" => "Honda Civic Sedan 2024",
            "s_description" => "Carro completo automatico",
        ]);
        $detected = olx_smart_detect_category($this->dao->dao, "carr", "carr");
        $this->assertNotNull($detected);
        $this->assertSame(31, (int) $detected["id"]);
    }
}
