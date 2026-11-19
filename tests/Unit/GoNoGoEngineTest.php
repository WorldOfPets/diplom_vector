<?php

namespace Tests\Unit;

use App\Services\Trainers\Engines\GoNoGoEngine;
use PHPUnit\Framework\TestCase;

class GoNoGoEngineTest extends TestCase
{
    public function test_go_no_go_counts_hits_false_alarms_and_misses(): void
    {
        $result = (new GoNoGoEngine)->score([], ['trials' => [
            ['type' => 'go', 'reacted' => true, 'rt' => 300],
            ['type' => 'nogo', 'reacted' => false, 'rt' => null],
            ['type' => 'go', 'reacted' => false, 'rt' => null],
            ['type' => 'nogo', 'reacted' => true, 'rt' => 400],
        ]]);

        $this->assertSame(50.0, $result->percent);
        $this->assertSame(1, $result->metrics['false_alarms']);
        $this->assertSame(1, $result->metrics['misses']);
        $this->assertSame(300, $result->metrics['mean_reaction_ms']);
    }

    public function test_go_no_go_without_trials_returns_zero(): void
    {
        $result = (new GoNoGoEngine)->score([], []);

        $this->assertSame(0.0, $result->percent);
    }
}
