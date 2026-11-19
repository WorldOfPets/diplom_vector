<?php

namespace Tests\Unit;

use App\Services\Trainers\Engines\StroopEngine;
use PHPUnit\Framework\TestCase;

class StroopEngineTest extends TestCase
{
    public function test_stroop_scores_correct_choices_and_keeps_answers_server_side(): void
    {
        $engine = new StroopEngine;
        $config = [
            'items' => [
                ['face' => '😊', 'word' => 'ЗЛОСТЬ', 'answer' => 'joy'],
                ['face' => '😠', 'word' => 'РАДОСТЬ', 'answer' => 'anger'],
            ],
            'options' => [
                ['id' => 'joy', 'label' => 'Радость'],
                ['id' => 'anger', 'label' => 'Злость'],
            ],
        ];

        $result = $engine->score($config, ['rounds' => [
            ['index' => 0, 'choice' => 'joy', 'ms' => 500],
            ['index' => 1, 'choice' => 'anger', 'ms' => 700],
        ]]);

        $this->assertSame(100.0, $result->percent);
        $this->assertSame(2, $result->metrics['correct']);
        $this->assertSame(600, $result->metrics['mean_reaction_ms']);

        $public = $engine->publicConfig($config);
        $this->assertArrayNotHasKey('answer', $public['items'][0]);
    }

    public function test_stroop_counts_wrong_and_missing_answers(): void
    {
        $engine = new StroopEngine;
        $config = [
            'items' => [
                ['face' => '😊', 'word' => 'ЗЛОСТЬ', 'answer' => 'joy'],
                ['face' => '😠', 'word' => 'РАДОСТЬ', 'answer' => 'anger'],
            ],
        ];

        $result = $engine->score($config, ['rounds' => [
            ['index' => 0, 'choice' => 'anger', 'ms' => 500],
        ]]);

        $this->assertSame(0.0, $result->percent);
        $this->assertSame(1, $result->metrics['answered']);
        $this->assertSame(1, $result->metrics['unanswered']);
    }
}
