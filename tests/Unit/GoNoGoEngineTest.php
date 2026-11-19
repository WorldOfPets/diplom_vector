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
        $this->assertNull($result->metrics['accuracy_after_flip']);
    }

    public function test_go_no_go_splits_accuracy_before_and_after_the_rule_flip(): void
    {
        $result = (new GoNoGoEngine)->score([], ['trials' => [
            ['type' => 'go', 'reacted' => true, 'rt' => 400],
            ['type' => 'go', 'reacted' => true, 'rt' => 420],
            ['type' => 'nogo', 'reacted' => false],
            ['type' => 'go', 'reacted' => false],
            ['type' => 'go', 'reacted' => false, 'flipped' => true],
            ['type' => 'go', 'reacted' => false, 'flipped' => true],
            ['type' => 'nogo', 'reacted' => true, 'flipped' => true],
            ['type' => 'nogo', 'reacted' => false, 'flipped' => true],
        ]]);

        $this->assertSame(50.0, $result->percent);
        $this->assertSame(75.0, $result->metrics['accuracy_before_flip']);
        $this->assertSame(25.0, $result->metrics['accuracy_after_flip']);

        $this->assertContains(
            'После смены правил точность просела: срабатывала старая привычка. Это и есть точка роста — здесь тренируется переключение, а не только торможение.',
            $result->feedback,
        );
    }

    public function test_go_no_go_without_trials_returns_zero(): void
    {
        $result = (new GoNoGoEngine)->score([], []);

        $this->assertSame(0.0, $result->percent);
    }
}
