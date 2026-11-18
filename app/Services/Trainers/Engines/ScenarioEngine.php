<?php

namespace App\Services\Trainers\Engines;

use App\Services\Trainers\BaseEngine;
use App\Services\Trainers\EngineResult;

class ScenarioEngine extends BaseEngine
{
    public function key(): string
    {
        return 'scenario';
    }

    public function label(): string
    {
        return 'Сюжетный сценарий';
    }

    public function schema(): array
    {
        return [
            'prompt' => ['type' => 'textarea', 'label' => 'Инструкция'],
            'start' => ['type' => 'text', 'label' => 'Стартовый узел'],
            'nodes' => ['type' => 'json', 'label' => 'Узлы сценария'],
            'scoring' => ['type' => 'json', 'label' => 'Ключ'],
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function publicConfig(array $config): array
    {
        unset($config['scoring']);

        foreach ($config['nodes'] ?? [] as $nodeKey => $node) {
            foreach ($node['options'] ?? [] as $optionKey => $option) {
                unset($config['nodes'][$nodeKey]['options'][$optionKey]['score']);
            }
        }

        return $config;
    }

    public function score(array $config, array $response): EngineResult
    {
        $nodes = $config['nodes'] ?? [];
        $current = $config['start'] ?? array_key_first($nodes);
        $choices = array_values($response['choices'] ?? []);
        $score = 0.0;
        $visited = [];
        $lastNode = null;

        for ($step = 0; $step < 20; $step++) {
            $node = $nodes[$current] ?? null;
            if ($node === null) {
                break;
            }

            $visited[] = $current;
            $lastNode = $node;

            if (empty($node['options'])) {
                break;
            }

            $choice = $choices[$step] ?? null;
            if ($choice === null || ! isset($node['options'][$choice])) {
                break;
            }

            $option = $node['options'][$choice];
            $score += (float) ($option['score'] ?? 0);
            $current = $option['next'] ?? $current;
        }

        $maxScore = (float) ($config['scoring']['max_score'] ?? 10);
        $percent = $maxScore > 0 ? min(100, round($score / $maxScore * 100, 1)) : 0.0;

        return new EngineResult(
            percent: $percent,
            metrics: [
                'raw_score' => $score,
                'nodes_visited' => count($visited),
                'finished' => empty($lastNode['options'] ?? []) ? 1 : 0,
            ],
            feedback: [
                $lastNode['outcome'] ?? 'Сценарий завершён. Сравните свой путь с альтернативными: что вы сохранили, а что потеряли?',
            ],
            summary: $lastNode['outcome'] ?? null,
        );
    }
}
