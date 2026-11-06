<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\olx_analytics;

use Tests\Support\DatabaseTestCase;

final class IndexTest extends DatabaseTestCase
{
    public function testAnalyticsMenuFunctionIsDefined(): void
    {
        $this->assertTrue(function_exists("olx_analytics_menu"));
    }
}
