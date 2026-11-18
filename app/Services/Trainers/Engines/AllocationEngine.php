<?php

namespace App\Services\Trainers\Engines;

use App\Services\Trainers\BaseEngine;
use App\Services\Trainers\EngineResult;

class AllocationEngine extends BaseEngine
{
    public function key(): string
    {
        return 'allocation';
    }

    public function label(): string
    {
        return 'Распределение ресурса';
    }

    public function schema(): array
    {
        return [
            'prompt' => ['type' => 'text', 'label' => 'Инструкция'],
            'budget' => ['type' => 'number', 'label' => 'Бюджет'],
            'items' => ['type' => 'repeater', 'label' => 'Варианты', 'fields' => [
                'id' => ['type' => 'text', 'label' => 'ID'],
                'label' => ['type' => 'text', 'label' => 'Название'],
                'description' => ['type' => 'textarea', 'label' => 'Описание'],
                'image' => ['type' => 'media', 'label' => 'Картинка'],
                'category' => ['type' => 'text', 'label' => 'Категория'],
            ]],
            'categories' => ['type' => 'repeater', 'label' => 'Категории', 'fields' => [
                'key' => ['type' => 'text', 'label' => 'Ключ'],
                'label' => ['type' => 'text', 'label' => 'Название'],
                'color' => ['type' => 'color', 'label' => 'Цвет'],
            ]],
        ];
    }

    public function score(array $config, array $response): EngineResult
    {
        $items = collect($this->items($config))->keyBy('id');
        $budget = max((int) ($config['budget'] ?? 100), 1);
        $amounts = $response['amounts'] ?? [];
        $categories = $this->categoryLabels($config);

        $clean = [];
        foreach ($amounts as $id => $amount) {
            if (! $items->has($id)) {
                continue;
            }

            $clean[$id] = max(0, min($budget, (int) $amount));
        }

        $total = array_sum($clean);

        if ($total > $budget) {
            $factor = $budget / $total;
            $clean = array_map(fn (int $amount): int => (int) floor($amount * $factor), $clean);
            $total = array_sum($clean);
        }

        $weights = [];
        foreach ($clean as $id => $amount) {
            $category = $items->get($id)['category'] ?? 'other';
            $weights[$category] = ($weights[$category] ?? 0) + $amount;
        }

        foreach (array_keys($categories) as $key) {
            $weights[$key] = $weights[$key] ?? 0;
        }

        $percents = $this->toPercents($weights);
        $metrics = [];
        foreach ($percents as $key => $percent) {
            $metrics['motive_'.$key] = $percent;
        }

        arsort($percents);
        $topKey = array_key_first($percents);
        $topLabel = $categories[$topKey] ?? $topKey;

        return new EngineResult(
            percent: $total > 0 ? 100.0 : 0.0,
            metrics: $metrics,
            feedback: [
                $total > 0
                    ? "Больше всего ресурса ушло на «{$topLabel}». Проверьте, насколько этот выбор про ваш реальный приоритет, а не про ожидания других."
                    : 'Распределите хотя бы часть бюджета — без выбора профиль не построить.',
            ],
            summary: $topLabel,
        );
    }
}
