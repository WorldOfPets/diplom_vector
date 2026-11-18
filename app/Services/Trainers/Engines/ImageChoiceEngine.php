<?php

namespace App\Services\Trainers\Engines;

use App\Services\Trainers\BaseEngine;
use App\Services\Trainers\EngineResult;

class ImageChoiceEngine extends BaseEngine
{
    public function key(): string
    {
        return 'image_choice';
    }

    public function label(): string
    {
        return 'Сцена + выбор эмоций';
    }

    public function schema(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => 'Заголовок'],
            'prompt' => ['type' => 'text', 'label' => 'Инструкция'],
            'image' => ['type' => 'media', 'label' => 'Изображение'],
            'image_alt' => ['type' => 'text', 'label' => 'Alt-текст'],
            'choose' => ['type' => 'number', 'label' => 'Сколько эмоций выбрать'],
            'emotions' => ['type' => 'repeater', 'label' => 'Эмоции', 'fields' => [
                'id' => ['type' => 'text', 'label' => 'ID'],
                'label' => ['type' => 'text', 'label' => 'Название'],
            ]],
            'needs' => ['type' => 'repeater', 'label' => 'Потребности', 'fields' => [
                'id' => ['type' => 'text', 'label' => 'ID'],
                'label' => ['type' => 'text', 'label' => 'Название'],
            ]],
            'scoring' => ['type' => 'json', 'label' => 'Ключ'],
        ];
    }

    public function score(array $config, array $response): EngineResult
    {
        $scoring = $config['scoring'] ?? [];
        $selected = array_values(array_filter($response['emotions'] ?? [], 'is_string'));
        $need = $response['need'] ?? null;
        $intensity = (int) ($response['intensity'] ?? 3);

        $weights = $scoring['emotions'] ?? [];
        $maxEmotionScore = array_sum($weights);
        $emotionScore = 0.0;
        $falsePositives = 0;

        foreach ($selected as $id) {
            if (isset($weights[$id])) {
                $emotionScore += (float) $weights[$id];
            } else {
                $falsePositives++;
            }
        }

        $emotionScore = max(0, $emotionScore - $falsePositives * 0.2);
        $emotionPart = $maxEmotionScore > 0 ? min(1, $emotionScore / $maxEmotionScore) * 70 : 0;

        $needPart = 0.0;
        if ($emotionScore > 0 && isset($scoring['need_weights'][$need])) {
            $needPart = 20 * (float) $scoring['need_weights'][$need];
        } elseif ($emotionScore > 0 && ($scoring['need'] ?? null) === $need) {
            $needPart = 20.0;
        }

        $intensityPart = 0.0;
        $target = $scoring['intensity_target'] ?? null;
        $tolerance = (int) ($scoring['intensity_tolerance'] ?? 1);
        if ($emotionScore > 0 && $target !== null && abs($intensity - (int) $target) <= $tolerance) {
            $intensityPart = 10.0;
        }

        $percent = round($emotionPart + $needPart + $intensityPart, 1);

        return new EngineResult(
            percent: min(100, $percent),
            metrics: [
                'emotion_match' => round($emotionPart, 1),
                'need_match' => $needPart,
                'intensity_match' => $intensityPart,
                'false_positives' => $falsePositives,
                'intensity' => $intensity,
            ],
            feedback: [
                $falsePositives > 0
                    ? 'Есть эмоции, которых в сцене, скорее всего, нет. Проверьте, что вы опираетесь на контекст, а не на первое впечатление.'
                    : 'Выбирайте не только «плохо/хорошо», но и точное слово: это помогает понять, что человеку нужно.',
            ],
            summary: $scoring['summary'] ?? null,
        );
    }
}
