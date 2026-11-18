<?php

namespace App\Services\Trainers\Engines;

use App\Services\Trainers\BaseEngine;
use App\Services\Trainers\EngineResult;

class CameraEngine extends BaseEngine
{
    public function key(): string
    {
        return 'camera';
    }

    public function label(): string
    {
        return 'Камера: мимика';
    }

    public function schema(): array
    {
        return [
            'prompt' => ['type' => 'text', 'label' => 'Инструкция'],
            'targets' => ['type' => 'repeater', 'label' => 'Целевые эмоции', 'fields' => [
                'id' => ['type' => 'text', 'label' => 'ID'],
                'label' => ['type' => 'text', 'label' => 'Название'],
                'threshold' => ['type' => 'number', 'label' => 'Порог, %'],
            ]],
        ];
    }

    public function score(array $config, array $response): EngineResult
    {
        if (isset($response['targets']) && is_array($response['targets'])) {
            $targets = $response['targets'];
            $total = max(1, (int) ($response['total'] ?? count($config['targets'] ?? [])));
            $completed = min($total, count($targets));
            $confidences = array_map(
                fn (array $item): float => max(0, min(100, (float) ($item['confidence'] ?? 0))),
                array_values($targets),
            );
            $average = $confidences !== [] ? array_sum($confidences) / count($confidences) : 0.0;
            $percent = round($completed / $total * 100, 1);

            return new EngineResult(
                percent: $percent,
                metrics: [
                    'confidence' => round($average, 1),
                    'completed' => $completed,
                    'total' => $total,
                    'hold_ms' => (int) ($response['duration_ms'] ?? 0),
                ],
                feedback: [
                    $completed >= $total
                        ? 'Вы удержали все '.$total.' выражений. Средняя уверенность модели — '.round($average).'%. Теперь попробуйте заметить, какое внутреннее состояние помогало удерживать каждое выражение.'
                        : 'Пройдено '.$completed.' из '.$total.'. Продолжайте, пока не соберёте все эмоции.',
                ],
            );
        }

        $confidence = max(0, min(100, (float) ($response['confidence'] ?? 0)));
        $duration = max(0, (int) ($response['duration_ms'] ?? 0));
        $emotion = $response['emotion'] ?? null;
        $target = $response['target'] ?? null;

        return new EngineResult(
            percent: round($confidence, 1),
            metrics: [
                'confidence' => $confidence,
                'hold_ms' => $duration,
                'match' => $emotion === $target ? 1 : 0,
            ],
            feedback: [
                $confidence >= 80
                    ? 'Выражение удержано уверенно. Обратите внимание на внутреннее состояние: мимика убедительнее, когда вы действительно вспоминаете ситуацию, а не «делаете лицо».'
                    : 'Модель не увидела нужное выражение с достаточной уверенностью. Попробуйте усилить мимику и добавить контекст: вспомните ситуацию, в которой вы это чувствовали.',
            ],
        );
    }
}
