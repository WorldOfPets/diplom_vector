<?php

namespace Tests\Unit;

use App\Support\Chart;
use PHPUnit\Framework\TestCase;

class ChartScaleTest extends TestCase
{
    public function test_radar_max_rounds_up_to_a_readable_ceiling(): void
    {
        $this->assertSame(30, Chart::radarMax([17, 29, 11, 23, 17, 0]));
        $this->assertSame(10, Chart::radarMax([5, 7, 3]));
        $this->assertSame(80, Chart::radarMax([60, 80, 45]));
        $this->assertSame(100, Chart::radarMax([95, 40]));
    }

    public function test_radar_max_falls_back_to_ten_without_data(): void
    {
        $this->assertSame(10, Chart::radarMax([]));
        $this->assertSame(10, Chart::radarMax([0, 0]));
    }
}
