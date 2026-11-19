<?php

namespace Tests\Unit;

use App\Services\Trainers\Engines\ScenarioEngine;
use PHPUnit\Framework\TestCase;

class ScenarioDialogueTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        return [
            'start' => 'start',
            'nodes' => [
                'start' => [
                    'text' => 'Первая реплика',
                    'options' => [
                        ['label' => 'Слабая реакция', 'next' => 'weak', 'score' => 1],
                        ['label' => 'Сильная реакция', 'next' => 'strong', 'score' => 3],
                    ],
                ],
                'weak' => [
                    'text' => 'Слабая ветка',
                    'options' => [
                        ['label' => 'Ещё шаг', 'next' => 'end', 'score' => 1],
                    ],
                ],
                'strong' => [
                    'text' => 'Сильная ветка',
                    'options' => [
                        ['label' => 'Ещё шаг', 'next' => 'end', 'score' => 3],
                    ],
                ],
                'end' => [
                    'text' => 'Финал',
                    'outcome' => 'Договорились',
                ],
            ],
            'scoring' => ['max_score' => 6],
        ];
    }

    public function test_multi_step_dialogue_scores_the_full_path(): void
    {
        $result = (new ScenarioEngine)->score($this->config(), ['choices' => [1, 0]]);

        $this->assertSame(100.0, $result->percent);
        $this->assertSame('Договорились', $result->summary);
        $this->assertSame(1, $result->metrics['finished']);
    }

    public function test_partial_dialogue_scores_only_the_chosen_steps(): void
    {
        $result = (new ScenarioEngine)->score($this->config(), ['choices' => [0, 0]]);

        $this->assertSame(33.3, $result->percent);
        $this->assertSame(2.0, $result->metrics['raw_score']);
    }
}
