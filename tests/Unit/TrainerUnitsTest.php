<?php

namespace Tests\Unit;

use App\Services\ContentProgressService;
use PHPUnit\Framework\TestCase;

class TrainerUnitsTest extends TestCase
{
    public function test_trainer_units_follow_the_grading_scale(): void
    {
        $this->assertSame(0, ContentProgressService::trainerUnits(0.0));
        $this->assertSame(0, ContentProgressService::trainerUnits(49.9));
        $this->assertSame(1, ContentProgressService::trainerUnits(50.0));
        $this->assertSame(1, ContentProgressService::trainerUnits(89.9));
        $this->assertSame(2, ContentProgressService::trainerUnits(90.0));
        $this->assertSame(2, ContentProgressService::trainerUnits(100.0));
    }
}
