<?php

namespace App\Services\Trainers\Engines;

use App\Services\Trainers\BaseEngine;
use App\Services\Trainers\EngineResult;

class ShieldEngine extends BaseEngine
{
    public function key(): string
    {
        return 'shield';
    }

    public function label(): string
    {
        return 'Профессиональный герб';
    }

    public function schema(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => 'Заголовок'],
            'prompt' => ['type' => 'textarea', 'label' => 'Инструкция'],
            'color' => ['type' => 'color', 'label' => 'Цвет герба'],
            'sectors' => ['type' => 'repeater', 'label' => 'Секторы', 'fields' => [
                'key' => ['type' => 'text', 'label' => 'Ключ'],
                'label' => ['type' => 'text', 'label' => 'Название'],
                'hint' => ['type' => 'text', 'label' => 'Подсказка'],
                'emoji' => ['type' => 'text', 'label' => 'Эмодзи'],
            ]],
            'symbols' => ['type' => 'repeater', 'label' => 'Символы', 'fields' => [
                'id' => ['type' => 'text', 'label' => 'ID'],
                'emoji' => ['type' => 'text', 'label' => 'Эмодзи'],
                'label' => ['type' => 'text', 'label' => 'Название'],
            ]],
        ];
    }

    public function score(array $config, array $response): EngineResult
    {
        $sectors = $config['sectors'] ?? [];
        $values = is_array($response['sectors'] ?? null) ? $response['sectors'] : [];
        $filled = 0;
        $metrics = [];

        foreach ($sectors as $sector) {
            $key = $sector['key'];
            $value = trim((string) ($values[$key] ?? ''));
            $metrics['sector_'.$key] = mb_strlen($value);
            if ($value !== '') {
                $filled++;
            }
        }

        $total = count($sectors);
        $percent = $total > 0 ? round($filled / $total * 100, 1) : 100.0;

        return new EngineResult(
            percent: $percent,
            metrics: $metrics,
            feedback: [
                $percent >= 80
                    ? 'Герб собран: в нём есть достижение, значимое событие, качества и девиз. Это опора для профессионального самоопределения.'
                    : 'Заполните больше секторов. Герб — не отчёт, а способ увидеть свою профессиональную опору.',
            ],
        );
    }
}
