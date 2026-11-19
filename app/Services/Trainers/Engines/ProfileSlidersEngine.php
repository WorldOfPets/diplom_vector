<?php

namespace App\Services\Trainers\Engines;

use App\Services\Trainers\BaseEngine;
use App\Services\Trainers\EngineResult;

class ProfileSlidersEngine extends BaseEngine
{
    public function key(): string
    {
        return 'profile_sliders';
    }

    public function label(): string
    {
        return 'Профиль по шкалам';
    }

    public function schema(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => 'Заголовок'],
            'prompt' => ['type' => 'text', 'label' => 'Инструкция'],
            'min' => ['type' => 'number', 'label' => 'Минимум'],
            'max' => ['type' => 'number', 'label' => 'Максимум'],
            'areas' => ['type' => 'repeater', 'label' => 'Сферы', 'fields' => [
                'key' => ['type' => 'text', 'label' => 'Ключ'],
                'label' => ['type' => 'text', 'label' => 'Название'],
                'color' => ['type' => 'color', 'label' => 'Цвет'],
            ]],
        ];
    }

    public function score(array $config, array $response): EngineResult
    {
        $areas = $config['areas'] ?? [];
        $values = $response['values'] ?? [];
        $focus = $response['focus'] ?? null;
        $actions = array_values(array_filter($response['actions'] ?? [], fn ($value): bool => is_string($value) && trim($value) !== ''));

        $max = (int) ($config['max'] ?? 10);
        $metrics = [];

        foreach ($areas as $area) {
            $key = $area['key'];
            $value = max(0, min($max, (int) ($values[$key] ?? 0)));
            $metrics['area_'.$key] = $value;
        }

        $metrics['actions_count'] = count($actions);

        // Every slider always has a value, and 0 is a valid answer, so the screen is complete once opened.
        $percent = 100.0;
        $focusLabel = collect($areas)->firstWhere('key', $focus)['label'] ?? null;

        return new EngineResult(
            percent: $percent,
            metrics: $metrics,
            feedback: [
                $focusLabel
                    ? "Вы выбрали зону роста «{$focusLabel}». Один шаг в ней важнее, чем идеальный план по всем сферам."
                    : 'Выберите одну сферу, которую хотите сдвинуть в ближайшие две недели.',
                $actions !== [] ? 'Записано шагов: '.count($actions).'.' : 'Добавьте хотя бы один конкретный шаг.',
            ],
            summary: $focusLabel,
        );
    }
}
