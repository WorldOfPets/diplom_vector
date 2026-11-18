<?php

namespace Tests\Unit;

use App\Services\EiScoringService;
use App\Services\MotivationScoringService;
use PHPUnit\Framework\TestCase;

class ScoringTest extends TestCase
{
    public function test_ei_levels_are_criterion_based(): void
    {
        $service = new EiScoringService;

        $this->assertSame('high', $service->level(90));
        $this->assertSame('above_average', $service->level(75));
        $this->assertSame('average', $service->level(60));
        $this->assertSame('below_average', $service->level(45));
        $this->assertSame('low', $service->level(20));
    }

    public function test_motivation_levels_are_criterion_based(): void
    {
        $service = new MotivationScoringService;

        $this->assertSame('high', $service->level(85));
        $this->assertSame('above_average', $service->level(65));
        $this->assertSame('average', $service->level(45));
        $this->assertSame('below_average', $service->level(25));
        $this->assertSame('low', $service->level(10));
    }
}
