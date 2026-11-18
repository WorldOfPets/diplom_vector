<?php

namespace App\Services\Trainers\Engines;

use App\Services\Trainers\BaseEngine;
use App\Services\Trainers\EngineResult;

class BestWorstEngine extends BaseEngine
{
    public function key(): string
    {
        return 'best_worst';
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function publicConfig(array $config): array
    {
        $config = parent::publicConfig($config);
        unset($config['feedback']);

        foreach ($config['options'] ?? [] as $index => $option) {
            unset($config['options'][$index]['scores']);
        }

        return $config;
    }

    public function label(): string
    {
        return 'Лучшая и худшая реакция';
    }

    public function schema(): array
    {
        return [
            'mode' => ['type' => 'select', 'label' => 'Режим', 'options' => ['best_worst' => 'Лучшая/худшая', 'profile' => 'Профиль стратегий']],
            'prompt' => ['type' => 'textarea', 'label' => 'Инструкция'],
            'scenario' => ['type' => 'textarea', 'label' => 'Ситуация'],
            'options' => ['type' => 'repeater', 'label' => 'Варианты', 'fields' => [
                'id' => ['type' => 'text', 'label' => 'ID'],
                'label' => ['type' => 'textarea', 'label' => 'Текст'],
                'scores' => ['type' => 'json', 'label' => 'Баллы по категориям'],
            ]],
            'categories' => ['type' => 'repeater', 'label' => 'Категории', 'fields' => [
                'key' => ['type' => 'text', 'label' => 'Ключ'],
                'label' => ['type' => 'text', 'label' => 'Название'],
                'color' => ['type' => 'color', 'label' => 'Цвет'],
            ]],
            'scoring' => ['type' => 'json', 'label' => 'Ключ'],
        ];
    }

    public function score(array $config, array $response): EngineResult
    {
        $mode = $config['mode'] ?? 'best_worst';
        $options = collect($config['options'] ?? [])->keyBy('id');

        if ($mode === 'profile') {
            $choice = $response['choice'] ?? null;
            $option = $options->get($choice);

            if ($option === null) {
                return new EngineResult(0.0, [], ['Выберите один вариант, чтобы построить профиль.']);
            }

            $scores = $option['scores'] ?? [];
            $percents = $this->toPercents(array_map('floatval', $scores));
            $metrics = [];
            foreach ($percents as $key => $value) {
                $metrics['strategy_'.$key] = $value;
            }

            arsort($percents);
            $topKey = array_key_first($percents);
            $labels = $this->categoryLabels($config);

            return new EngineResult(
                percent: 100.0,
                metrics: $metrics,
                feedback: [
                    'Ведущая стратегия — «'.($labels[$topKey] ?? $topKey).'». У каждой стратегии есть уместный контекст: важно не «правильно», а осознанно.',
                ],
                summary: $labels[$topKey] ?? $topKey,
            );
        }

        $best = $response['best'] ?? null;
        $worst = $response['worst'] ?? null;
        $keyBest = $config['scoring']['best'] ?? null;
        $keyWorst = $config['scoring']['worst'] ?? null;

        if ($best === null || $worst === null || $best === $worst) {
            return new EngineResult(0.0, [], ['Выберите два разных варианта: лучший и худший.']);
        }

        $score = 0.0;
        if ($best === $keyBest) {
            $score += 50;
        }
        if ($worst === $keyWorst) {
            $score += 50;
        }
        if ($best === $keyWorst && $worst === $keyBest) {
            $score = max(0, $score - 50);
        }

        return new EngineResult(
            percent: $score,
            metrics: [
                'best_correct' => $best === $keyBest ? 1 : 0,
                'worst_correct' => $worst === $keyWorst ? 1 : 0,
            ],
            feedback: [
                $config['feedback'][$keyBest] ?? 'Сравните выбранную лучшую реакцию с ключом: что в ней сохраняет отношения и границы одновременно?',
            ],
        );
    }
}
