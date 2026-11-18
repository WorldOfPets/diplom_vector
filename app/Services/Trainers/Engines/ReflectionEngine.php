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

        $percent = $required > 0 ? min(100, round($filled / $required * 100, 1)) : 100.0;

        $feedback = [];
        if ($deltas !== []) {
            arsort($deltas);
            $bestKey = array_key_first($deltas);
            $best = $deltas[$bestKey];
            $label = collect($config['sliders_after'] ?? [])->firstWhere('key', $bestKey)['label'] ?? $bestKey;

            $feedback[] = $best > 0
                ? "Заметнее всего снизилось напряжение по шкале «{$label}»: −{$best}."
                : 'Пока изменений нет — это нормально. Можно вернуться к упражнению позже.';
        }

        if ($actions !== []) {
            $feedback[] = 'Вы выбрали '.count($actions).' способ(а) самопомощи. Важно не количество, а один способ, который реально сработает завтра.';
        }

        return new EngineResult(
            percent: $percent,
            metrics: $metrics,
            feedback: $feedback,
            summary: $response['card_title'] ?? null,
        );
    }
}
