<?php

namespace App\Services\Trainers\Engines;

use App\Services\Trainers\BaseEngine;
use App\Services\Trainers\EngineResult;

class StroopEngine extends BaseEngine
{
    public function key(): string
    {
        return 'stroop';
    }

    public function label(): string
    {
        return 'Эмоциональный Струп';
    }

    public function schema(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => 'Заголовок'],
            'prompt' => ['type' => 'textarea', 'label' => 'Инструкция'],
            'time_limit_ms' => ['type' => 'number', 'label' => 'Время на раунд, мс'],
            'options' => ['type' => 'json', 'label' => 'Варианты ответа (JSON)'],
            'items' => ['type' => 'json', 'label' => 'Раунды: лицо, слово, ответ (JSON)'],
        ];
    }

    public function publicConfig(array $config): array
    {
        $config = parent::publicConfig($config);

        foreach ($config['items'] ?? [] as $index => $item) {
            unset($config['items'][$index]['answer']);
        }

        return $config;
    }

    public function score(array $config, array $response): EngineResult
    {
        $items = is_array($config['items'] ?? null) ? array_values($config['items']) : [];
        $total = count($items);
        $rounds = is_array($response['rounds'] ?? null) ? $response['rounds'] : [];
        $byIndex = [];

        foreach (array_slice($rounds, 0, 100) as $round) {
            if (! is_array($round) || ! isset($round['index']) || ! is_numeric($round['index'])) {
                continue;
            }

            $byIndex[(int) $round['index']] = $round;
        }

        $correct = 0;
        $answered = 0;
        $times = [];

        foreach ($items as $index => $item) {
            $round = $byIndex[$index] ?? null;
            $choice = is_array($round) ? ($round['choice'] ?? null) : null;

            if (is_string($choice) && $choice !== '') {
                $answered++;
            }

            if (is_string($choice) && $choice === ($item['answer'] ?? null)) {
                $correct++;
            }

            if (is_array($round) && isset($round['ms']) && is_numeric($round['ms'])) {
                $times[] = max(0, min(60000, (int) $round['ms']));
            }
        }

        $percent = $total > 0 ? round($correct / $total * 100, 1) : 0.0;
        $mean = $times !== [] ? (int) round(array_sum($times) / count($times)) : null;

        return new EngineResult(
            percent: $percent,
            metrics: [
                'accuracy' => $percent,
                'correct' => $correct,
                'total' => $total,
                'answered' => $answered,
                'unanswered' => max(0, $total - $answered),
                'mean_reaction_ms' => $mean,
            ],
            feedback: [
                $percent >= 80
                    ? 'Вы хорошо удерживаете лицо и не даёте слову перетянуть внимание. Это и есть торможение помехи.'
                    : 'Слово-дистрактор перетягивает внимание на себя. Помогает пауза в полсекунды и взгляд на глаза и рот, а не на текст.',
                'Это упражнение на внимание и распознавание, а не диагноз. Скорость важна меньше точности.',
            ],
        );
    }
}
