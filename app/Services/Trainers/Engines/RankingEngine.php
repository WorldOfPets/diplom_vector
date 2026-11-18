<?php

namespace App\Services\Trainers\Engines;

use App\Services\Trainers\BaseEngine;
use App\Services\Trainers\EngineResult;

class RankingEngine extends BaseEngine
{
    public function key(): string
    {
        return 'ranking';
    }

    public function label(): string
    {
        return 'Ранжирование';
    }

    public function schema(): array
    {
        return [
            'prompt' => ['type' => 'text', 'label' => 'Инструкция'],
            'items' => ['type' => 'repeater', 'label' => 'Элементы', 'fields' => [
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
        $items = $this->items($config);
        $order = array_values(array_filter($response['order'] ?? [], 'is_string'));

        $byId = collect($items)->keyBy('id');
        $validOrder = array_values(array_filter($order, fn (string $id): bool => $byId->has($id)));

        $count = max(count($items), 1);
        $weights = [];
        $categories = $this->categoryLabels($config);

        foreach ($validOrder as $index => $id) {
            $item = $byId->get($id);
            $weight = max($count - $index, 1);
            $category = $item['category'] ?? 'other';
            $weights[$category] = ($weights[$category] ?? 0) + $weight;
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

        $feedback = [];
        if (isset($config['feedback'][$topKey])) {
            $feedback[] = $config['feedback'][$topKey];
        } else {
            $feedback[] = "Ведущий мотив — «{$topLabel}». Это не диагноз, а точка опоры для следующего шага.";
        }

        return new EngineResult(
            percent: count($validOrder) === count($items) ? 100.0 : round(count($validOrder) / $count * 100, 1),
            metrics: $metrics,
            feedback: $feedback,
            summary: $topLabel,
        );
    }
}
