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
        $phases = [
            'pre' => ['correct' => 0, 'total' => 0],
            'post' => ['correct' => 0, 'total' => 0],
        ];

        foreach ($trials as $trial) {
            $type = $trial['type'] ?? null;
            $reacted = (bool) ($trial['reacted'] ?? false);
            $rt = isset($trial['rt']) ? (int) $trial['rt'] : null;
            $isCorrect = false;

            if ($type === 'go') {
                if ($reacted) {
                    $correct++;
                    $isCorrect = true;
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
                    $isCorrect = true;
                }
            }

            $phase = ! empty($trial['flipped']) ? 'post' : 'pre';
            $phases[$phase]['total']++;

            if ($isCorrect) {
                $phases[$phase]['correct']++;
            }
        }

        $percent = round($correct / $total * 100, 1);
        $meanRt = $reactionTimes !== [] ? (int) round(array_sum($reactionTimes) / count($reactionTimes)) : null;

        $beforeFlip = $phases['pre']['total'] > 0
            ? round($phases['pre']['correct'] / $phases['pre']['total'] * 100, 1)
            : null;
        $afterFlip = $phases['post']['total'] > 0
            ? round($phases['post']['correct'] / $phases['post']['total'] * 100, 1)
            : null;

        $feedback = [
            $percent >= 80
                ? 'Хороший контроль импульса: вы вовремя тормозите реакцию на «триггер».'
                : 'Импульс пока срабатывает быстрее торможения. Это навык, который тренируется повтором.',
            'Игра измеряет не «силу воли», а скорость торможения в конкретной серии. Результат может меняться от усталости.',
        ];

        if ($beforeFlip !== null && $afterFlip !== null) {
            if ($afterFlip + 15 < $beforeFlip) {
                $feedback[] = 'После смены правил точность просела: срабатывала старая привычка. Это и есть точка роста — здесь тренируется переключение, а не только торможение.';
            } elseif ($afterFlip >= $beforeFlip - 5) {
                $feedback[] = 'Правило после смены вы удержали: переключение сработало.';
            }
        }

        return new EngineResult(
            percent: $percent,
            metrics: [
                'accuracy' => $percent,
                'accuracy_before_flip' => $beforeFlip,
                'accuracy_after_flip' => $afterFlip,
                'false_alarms' => $falseAlarms,
                'misses' => $misses,
                'mean_reaction_ms' => $meanRt,
                'trials' => $total,
            ],
            feedback: $feedback,
        );
    }
}
