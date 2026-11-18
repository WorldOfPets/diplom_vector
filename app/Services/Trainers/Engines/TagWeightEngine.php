<?php

namespace App\Services\Trainers\Engines;

use App\Services\Trainers\BaseEngine;
use App\Services\Trainers\EngineResult;

class TagWeightEngine extends BaseEngine
{
    public function key(): string
    {
        return 'tag_weight';
    }

    public function label(): string
    {
        return 'Теги с весами';
    }

    public function schema(): array
    {
        return [
            'prompt' => ['type' => 'text', 'label' => 'Инструкция'],
            'max_tags' => ['type' => 'number', 'label' => 'Максимум тегов'],
            'tags' => ['type' => 'repeater', 'label' => 'Теги', 'fields' => [
                'id' => ['type' => 'text', 'label' => 'ID'],
                'label' => ['type' => 'text', 'label' => 'Название'],
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
        $tags = collect($config['tags'] ?? [])->keyBy('id');
        $maxTags = max((int) ($config['max_tags'] ?? 8), 1);
        $selected = $response['tags'] ?? [];
        $categories = $this->categoryLabels($config);

        $clean = [];
        foreach ($selected as $id => $weight) {
            if (! $tags->has($id)) {
                continue;
            }

            $weight = (int) $weight;
            if ($weight < 1 || $weight > 3) {
                continue;
            }

            $clean[$id] = $weight;
        }

        $clean = array_slice($clean, 0, $maxTags, true);

        $weights = [];
        foreach ($clean as $id => $weight) {
            $category = $tags->get($id)['category'] ?? 'other';
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

        $topTags = collect($clean)
            ->sortDesc()
            ->take(3)
            ->map(fn (int $weight, string $id): string => $tags->get($id)['label'] ?? $id)
            ->values()
            ->all();

        arsort($percents);
        $topKey = array_key_first($percents);
        $topLabel = $categories[$topKey] ?? $topKey;

        return new EngineResult(
            percent: $clean !== [] ? 100.0 : 0.0,
            metrics: $metrics,
            feedback: [
                $clean !== []
                    ? 'Сильнее всего звучат: '.implode(', ', $topTags).". Ведущая группа — «{$topLabel}»."
                    : 'Выберите хотя бы один тег.',
            ],
            summary: $topLabel,
        );
    }
}
