<?php

namespace App\Services\Trainers\Engines;

use App\Services\Trainers\BaseEngine;
use App\Services\Trainers\EngineResult;

class GoNoGoEngine extends BaseEngine
{
    public function key(): string
    {
        return 'go_no_go';
    }

    public function label(): string
    {
        return 'Игра «Стоп-кран»';
    }

    public function schema(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => 'Название игры'],
            'prompt' => ['type' => 'textarea', 'label' => 'Инструкция'],
            'trials' => ['type' => 'number', 'label' => 'Проб'],
            'go_ratio' => ['type' => 'number', 'label' => 'Доля GO (0–1)'],
            'response_window_ms' => ['type' => 'number', 'label' => 'Окно реакции, мс'],
            'go_label' => ['type' => 'text', 'label' => 'Сигнал GO'],
            'nogo_label' => ['type' => 'text', 'label' => 'Сигнал NO-GO'],
            'go_hint' => ['type' => 'text', 'label' => 'Подсказка GO'],
            'nogo_hint' => ['type' => 'text', 'label' => 'Подсказка NO-GO'],
        ];
    }

    public function score(array $config, array $response): EngineResult
    {
        $trials = array_slice($response['trials'] ?? [], 0, 100);
        $total = count($trials);

        if ($total === 0) {
            return new EngineResult(0.0, [], ['Недостаточно данных. Пройдите игру ещё раз.']);
        }

        $correct = 0;
        $falseAlarms = 0;
        $misses = 0;
        $reactionTimes = [];

        foreach ($trials as $trial) {
            $type = $trial['type'] ?? null;
            $reacted = (bool) ($trial['reacted'] ?? false);
            $rt = isset($trial['rt']) ? (int) $trial['rt'] : null;

            if ($type === 'go') {
                if ($reacted) {
                    $correct++;
                    if ($rt !== null) {
                        $reactionTimes[] = $rt;
                    }
                } else {
                    $misses++;
                }
            }

            if ($type === 'nogo') {
                if ($reacted) {
                    $falseAlarms++;
                } else {
                    $correct++;
                }
            }
        }

        $percent = round($correct / $total * 100, 1);
        $meanRt = $reactionTimes !== [] ? (int) round(array_sum($reactionTimes) / count($reactionTimes)) : null;

        return new EngineResult(
            percent: $percent,
            metrics: [
                'accuracy' => $percent,
                'false_alarms' => $falseAlarms,
                'misses' => $misses,
                'mean_reaction_ms' => $meanRt,
                'trials' => $total,
            ],
            feedback: [
                $percent >= 80
                    ? 'Хороший контроль импульса: вы вовремя тормозите реакцию на «триггер».'
                    : 'Импульс пока срабатывает быстрее торможения. Это навык, который тренируется повтором.',
                'Игра измеряет не «силу воли», а скорость торможения в конкретной серии. Результат может меняться от усталости.',
            ],
        );
    }
}
