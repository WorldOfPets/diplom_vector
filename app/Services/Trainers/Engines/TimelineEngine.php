<?php

namespace App\Services\Trainers\Engines;

use App\Services\Trainers\BaseEngine;
use App\Services\Trainers\EngineResult;

class TimelineEngine extends BaseEngine
{
    public function key(): string
    {
        return 'timeline';
    }

    public function label(): string
    {
        return 'Маршрут по этапам';
    }

    public function schema(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => 'Заголовок'],
            'prompt' => ['type' => 'textarea', 'label' => 'Инструкция'],
            'columns' => ['type' => 'repeater', 'label' => 'Этапы', 'fields' => [
                'key' => ['type' => 'text', 'label' => 'Ключ'],
                'label' => ['type' => 'text', 'label' => 'Название'],
                'hint' => ['type' => 'text', 'label' => 'Подсказка'],
            ]],
        ];
    }

    public function score(array $config, array $response): EngineResult
    {
        $columns = $config['columns'] ?? [];
        $values = is_array($response['milestones'] ?? null) ? $response['milestones'] : [];
        $filled = 0;
        $metrics = [];

        foreach ($columns as $column) {
            $key = $column['key'];
            $value = trim((string) ($values[$key] ?? ''));
            $metrics['column_'.$key] = mb_strlen($value);
            if ($value !== '') {
                $filled++;
            }
        }

        $total = count($columns);
        $percent = $total > 0 ? round($filled / $total * 100, 1) : 100.0;

        return new EngineResult(
            percent: $percent,
            metrics: $metrics,
            feedback: [
                $percent >= 80
                    ? 'Маршрут собран. Теперь выберите один ближайший шаг — не весь путь сразу.'
                    : 'Заполните больше этапов. Даже примерный маршрут снижает тревогу неопределённости.',
            ],
        );
    }
}
