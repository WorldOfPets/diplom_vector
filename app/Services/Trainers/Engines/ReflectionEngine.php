<?php

namespace App\Services\Trainers\Engines;

use App\Services\Trainers\BaseEngine;
use App\Services\Trainers\EngineResult;

class ReflectionEngine extends BaseEngine
{
    public function key(): string
    {
        return 'reflection';
    }

    public function label(): string
    {
        return 'Рефлексивное упражнение';
    }

    public function schema(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => 'Заголовок'],
            'prompt' => ['type' => 'textarea', 'label' => 'Инструкция'],
            'scenario' => ['type' => 'textarea', 'label' => 'Сценарий (шаги с новой строки)'],
            'cards' => ['type' => 'repeater', 'label' => 'Карточки', 'fields' => [
                'id' => ['type' => 'text', 'label' => 'ID'],
                'title' => ['type' => 'text', 'label' => 'Название'],
                'image' => ['type' => 'media', 'label' => 'Картинка'],
                'description' => ['type' => 'textarea', 'label' => 'Описание'],
            ]],
            'sliders_before' => ['type' => 'repeater', 'label' => 'Шкалы до', 'fields' => [
                'key' => ['type' => 'text', 'label' => 'Ключ'],
                'label' => ['type' => 'text', 'label' => 'Название'],
                'min' => ['type' => 'number', 'label' => 'Минимум'],
                'max' => ['type' => 'number', 'label' => 'Максимум'],
            ]],
            'sliders_after' => ['type' => 'repeater', 'label' => 'Шкалы после', 'fields' => [
                'key' => ['type' => 'text', 'label' => 'Ключ'],
                'label' => ['type' => 'text', 'label' => 'Название'],
                'min' => ['type' => 'number', 'label' => 'Минимум'],
                'max' => ['type' => 'number', 'label' => 'Максимум'],
            ]],
            'actions' => ['type' => 'repeater', 'label' => 'Действия', 'fields' => [
                'id' => ['type' => 'text', 'label' => 'ID'],
                'label' => ['type' => 'text', 'label' => 'Название'],
            ]],
            'texts' => ['type' => 'repeater', 'label' => 'Поля', 'fields' => [
                'key' => ['type' => 'text', 'label' => 'Ключ'],
                'label' => ['type' => 'text', 'label' => 'Название'],
                'placeholder' => ['type' => 'text', 'label' => 'Подсказка'],
            ]],
            'actions_label' => ['type' => 'text', 'label' => 'Заголовок действий'],
            'actions_hint' => ['type' => 'text', 'label' => 'Подсказка к действиям'],
            'require_actions' => ['type' => 'boolean', 'label' => 'Требовать выбор действия'],
            'drawing' => ['type' => 'json', 'label' => 'Рисование (JSON)', 'hint' => 'Например: {"background":"body","palette":["#FF6B4A","#2D5BFF"],"min_strokes":2}'],
            'puppet' => ['type' => 'json', 'label' => 'Кукла (JSON)', 'hint' => 'Например: {"title":"Соберите позу","target":"confident","targets":{"confident":"уверенный ответ"}}'],
        ];
    }

    public function score(array $config, array $response): EngineResult
    {
        $before = $response['before'] ?? [];
        $after = $response['after'] ?? [];
        $actions = array_values(array_filter($response['actions'] ?? [], 'is_string'));
        $texts = $response['texts'] ?? [];

        $metrics = [];
        $deltas = [];

        foreach ($config['sliders_before'] ?? [] as $slider) {
            $key = $slider['key'];
            $value = (int) ($before[$key] ?? $slider['min'] ?? 0);
            $metrics['before_'.$key] = $value;
        }

        foreach ($config['sliders_after'] ?? [] as $slider) {
            $key = $slider['key'];
            $value = (int) ($after[$key] ?? $slider['min'] ?? 0);
            $metrics['after_'.$key] = $value;

            if (isset($metrics['before_'.$key])) {
                $delta = $value - $metrics['before_'.$key];
                $metrics['delta_'.$key] = $delta;
                $deltas[$key] = $delta;
            }
        }

        $metrics['actions_count'] = count($actions);

        $required = 0;
        $filled = 0;

        $required += count($config['cards'] ?? []) > 0 ? 1 : 0;
        $filled += ! empty($response['card']) ? 1 : 0;

        $required += count($config['sliders_before'] ?? []);
        $filled += count(array_filter($metrics, fn ($key): bool => str_starts_with((string) $key, 'before_'), ARRAY_FILTER_USE_KEY));

        $required += count($config['sliders_after'] ?? []);
        $filled += count(array_filter($metrics, fn ($key): bool => str_starts_with((string) $key, 'after_'), ARRAY_FILTER_USE_KEY));

        if (($config['require_actions'] ?? false) === true) {
            $required++;
            if ($actions !== []) {
                $filled++;
            }
        }

        $required += count($config['texts'] ?? []);
        $filled += count(array_filter($texts, fn ($value): bool => is_string($value) && trim($value) !== ''));

        $strokeCount = 0;
        $drawing = ! empty($config['drawing']) && is_array($config['drawing']) ? $config['drawing'] : null;

        if ($drawing !== null) {
            $strokes = is_array($response['strokes'] ?? null) ? $response['strokes'] : [];
            $strokeCount = count(array_filter(
                $strokes,
                fn (mixed $stroke): bool => is_array($stroke)
                    && is_array($stroke['points'] ?? null)
                    && count($stroke['points']) > 0,
            ));

            $metrics['strokes'] = $strokeCount;
            $required++;

            if ($strokeCount >= max(1, (int) ($drawing['min_strokes'] ?? 1))) {
                $filled++;
            }
        }

        $puppet = ! empty($config['puppet']) && is_array($config['puppet']) ? $config['puppet'] : null;
        $puppetReading = null;

        if ($puppet !== null) {
            $state = is_array($response['puppet'] ?? null) ? $response['puppet'] : [];
            $parts = ['head', 'shoulders', 'arms', 'posture'];
            $filledParts = count(array_filter(
                $parts,
                fn (string $part): bool => isset($state[$part]) && is_string($state[$part]) && $state[$part] !== '',
            ));

            $metrics['puppet_parts'] = $filledParts;
            $required++;

            if ($filledParts === count($parts)) {
                $filled++;
            }

            $puppetReading = is_string($state['state'] ?? null) ? $state['state'] : null;
        }

        $percent = $required > 0 ? min(100, round($filled / $required * 100, 1)) : 100.0;

        $feedback = [];
        if ($deltas !== []) {
            $bestKey = array_key_first($deltas);
            $best = $deltas[$bestKey];
            $maxAbs = abs($best);

            foreach ($deltas as $key => $value) {
                if (abs($value) > $maxAbs) {
                    $maxAbs = abs($value);
                    $bestKey = $key;
                    $best = $value;
                }
            }

            $label = collect($config['sliders_after'] ?? [])->firstWhere('key', $bestKey)['label'] ?? $bestKey;

            $feedback[] = $best !== 0
                ? "Наибольшее изменение — по шкале «{$label}»: ".($best > 0 ? '+' : '').$best.'.'
                : 'Пока без изменений — это нормально. Можно вернуться к упражнению позже.';
        }

        if ($actions !== []) {
            $feedback[] = 'Вы выбрали '.count($actions).' способ(а) самопомощи. Важно не количество, а один способ, который реально сработает завтра.';
        }

        if ($drawing !== null) {
            $minStrokes = max(1, (int) ($drawing['min_strokes'] ?? 1));

            if ($strokeCount >= $minStrokes) {
                $feedback[] = "Рисунок сохранён: {$strokeCount} штрих(ов). Посмотрите на него через минуту — что замечаете?";
            } elseif ($strokeCount > 0) {
                $left = $minStrokes - $strokeCount;
                $feedback[] = "Добавьте ещё {$left} штрих(а), чтобы завершить рисунок.";
            } else {
                $feedback[] = 'Добавьте хотя бы один штрих: рисунок помогает заметить состояние, даже если он некрасивый.';
            }
        }

        if ($puppetReading !== null) {
            $labels = is_array($puppet['targets'] ?? null) ? $puppet['targets'] : [];
            $readingLabel = $labels[$puppetReading] ?? ($puppetReading === 'neutral' ? 'нейтрально' : $puppetReading);
            $feedback[] = 'Поза читается как «'.$readingLabel.'». Проверьте, совпадает ли это с вашим состоянием и что хочется изменить.';
        }

        return new EngineResult(
            percent: $percent,
            metrics: $metrics,
            feedback: $feedback,
            summary: $response['card_title'] ?? null,
        );
    }
}
