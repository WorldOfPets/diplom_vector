<?php

namespace App\Services\Trainers\Engines;

use App\Services\Trainers\BaseEngine;
use App\Services\Trainers\EngineResult;

class BreathingEngine extends BaseEngine
{
    public function key(): string
    {
        return 'breathing';
    }

    public function label(): string
    {
        return 'Дыхательная практика';
    }

    public function schema(): array
    {
        return [
            'prompt' => ['type' => 'text', 'label' => 'Инструкция'],
            'cycles' => ['type' => 'number', 'label' => 'Циклов'],
            'pattern' => ['type' => 'repeater', 'label' => 'Фазы', 'fields' => [
                'phase' => ['type' => 'text', 'label' => 'Фаза'],
                'seconds' => ['type' => 'number', 'label' => 'Секунды'],
            ]],
        ];
    }

    public function score(array $config, array $response): EngineResult
    {
        $required = max((int) ($config['cycles'] ?? 4), 1);
        $completed = max(0, min((int) ($response['cycles_completed'] ?? 0), $required));
        $before = (int) ($response['before'] ?? 5);
        $after = (int) ($response['after'] ?? 5);

        $percent = round($completed / $required * 100, 1);
        $delta = $after - $before;

        $feedback = [];
        if ($completed >= $required) {
            $feedback[] = 'Практика завершена. Телесное расслабление работает не сразу: эффект накапливается при повторении.';
        } else {
            $feedback[] = 'Практика не завершена. Можно вернуться и пройти все циклы — без спешки.';
        }

        return new EngineResult(
            percent: $percent,
            metrics: [
                'cycles' => $completed,
                'cycles_required' => $required,
                'tension_before' => $before,
                'tension_after' => $after,
                'tension_delta' => $delta,
            ],
            feedback: $feedback,
        );
    }
}
