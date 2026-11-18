<?php

namespace App\Services\Trainers\Engines;

use App\Services\Trainers\BaseEngine;
use App\Services\Trainers\EngineResult;

class CategorizeEngine extends BaseEngine
{
    public function key(): string
    {
        return 'categorize';
    }

    public function label(): string
    {
        return 'Сортировка по категориям';
    }

    public function schema(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => 'Заголовок'],
            'prompt' => ['type' => 'textarea', 'label' => 'Инструкция'],
            'buckets' => ['type' => 'repeater', 'label' => 'Категории', 'fields' => [
                'id' => ['type' => 'text', 'label' => 'ID'],
                'label' => ['type' => 'text', 'label' => 'Название'],
                'color' => ['type' => 'color', 'label' => 'Цвет'],
            ]],
            'items' => ['type' => 'repeater', 'label' => 'Карточки', 'fields' => [
                'id' => ['type' => 'text', 'label' => 'ID'],
                'label' => ['type' => 'text', 'label' => 'Текст'],
                'emoji' => ['type' => 'text', 'label' => 'Эмодзи'],
            ]],
            'scoring' => ['type' => 'json', 'label' => 'Ключ (если есть правильная категория)'],
        ];
    }

    public function score(array $config, array $response): EngineResult
    {
        $buckets = collect($config['buckets'] ?? [])->keyBy('id');
        $items = collect($config['items'] ?? [])->keyBy('id');
        $assigned = is_array($response['buckets'] ?? null) ? $response['buckets'] : [];
        $correctMap = $config['scoring']['buckets'] ?? [];

        $counts = [];
        foreach ($buckets as $key => $bucket) {
            $counts[$key] = 0;
        }

        $correct = 0;
        $assignedCount = 0;

        foreach ($assigned as $itemId => $bucketId) {
            if (! $items->has($itemId) || ! $buckets->has($bucketId)) {
                continue;
            }

            $assignedCount++;
            $counts[$bucketId] = ($counts[$bucketId] ?? 0) + 1;

            if (($correctMap[$itemId] ?? null) === $bucketId) {
                $correct++;
            }
        }

        $total = $items->count();
        $hasKey = $correctMap !== [];
        $percent = $hasKey
            ? ($total > 0 ? round($correct / $total * 100, 1) : 0.0)
            : ($total > 0 ? round($assignedCount / $total * 100, 1) : 0.0);

        $metrics = [
            'assigned' => $assignedCount,
            'total' => $total,
            'correct' => $hasKey ? $correct : 0,
        ];

        $topBucket = null;
        $topCount = -1;
        foreach ($counts as $key => $count) {
            $metrics['bucket_'.$key] = $total > 0 ? round($count / $total * 100, 1) : 0;
            if ($count > $topCount) {
                $topCount = $count;
                $topBucket = $key;
            }
        }

        $topLabel = $buckets->get($topBucket)['label'] ?? '—';

        $feedback = $assignedCount === 0
            ? ['Пока ничего не разложено. Начните с одной карточки — этого достаточно.']
            : [
                $hasKey
                    ? "Верно разложено: {$correct} из {$total}. Ошибки — не провал, а материал для разбора."
                    : "Больше всего карточек ушло в категорию «{$topLabel}». Проверьте, отражает ли это ваш реальный опыт.",
            ];

        return new EngineResult(
            percent: $percent,
            metrics: $metrics,
            feedback: $feedback,
            summary: $topLabel,
        );
    }
}
