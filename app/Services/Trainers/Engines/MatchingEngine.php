<?php

namespace App\Services\Trainers\Engines;

use App\Services\Trainers\BaseEngine;
use App\Services\Trainers\EngineResult;

class MatchingEngine extends BaseEngine
{
    public function key(): string
    {
        return 'matching';
    }

    public function label(): string
    {
        return 'Сопоставление';
    }

    public function schema(): array
    {
        return [
            'prompt' => ['type' => 'text', 'label' => 'Инструкция'],
            'left' => ['type' => 'repeater', 'label' => 'Левая колонка', 'fields' => [
                'id' => ['type' => 'text', 'label' => 'ID'],
                'label' => ['type' => 'text', 'label' => 'Текст'],
            ]],
            'right' => ['type' => 'repeater', 'label' => 'Правая колонка', 'fields' => [
                'id' => ['type' => 'text', 'label' => 'ID'],
                'label' => ['type' => 'text', 'label' => 'Текст'],
            ]],
            'scoring' => ['type' => 'json', 'label' => 'Ключ (pairs)'],
        ];
    }

    public function score(array $config, array $response): EngineResult
    {
        $pairs = $config['scoring']['pairs'] ?? [];
        $answers = $response['pairs'] ?? [];
        $total = count($pairs);
        $correct = 0;
        $wrong = [];

        foreach ($pairs as $leftId => $rightId) {
            if (($answers[$leftId] ?? null) === $rightId) {
                $correct++;
            } else {
                $wrong[] = $leftId;
            }
        }

        $percent = $total > 0 ? round($correct / $total * 100, 1) : 0.0;

        return new EngineResult(
            percent: $percent,
            metrics: [
                'correct' => $correct,
                'total' => $total,
                'wrong' => count($wrong),
            ],
            feedback: [
                $percent >= 80
                    ? 'Сопоставление точное. Обратите внимание не только на слово, но и на контекст, в котором оно звучит.'
                    : 'Часть пар не совпала. Разберите ошибки: у похожих эмоций разные причины и разные действия.',
            ],
        );
    }
}
