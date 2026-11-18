<?php

namespace App\Services\Trainers\Engines;

use App\Services\Trainers\BaseEngine;
use App\Services\Trainers\EngineResult;

class ConstructorEngine extends BaseEngine
{
    public function key(): string
    {
        return 'constructor';
    }

    public function label(): string
    {
        return 'Конструктор высказывания';
    }

    public function schema(): array
    {
        return [
            'title' => ['type' => 'text', 'label' => 'Заголовок'],
            'prompt' => ['type' => 'text', 'label' => 'Инструкция'],
            'template' => ['type' => 'textarea', 'label' => 'Шаблон'],
            'fields' => ['type' => 'repeater', 'label' => 'Поля', 'fields' => [
                'key' => ['type' => 'text', 'label' => 'Ключ'],
                'label' => ['type' => 'text', 'label' => 'Название'],
                'hint' => ['type' => 'text', 'label' => 'Подсказка'],
                'placeholder' => ['type' => 'text', 'label' => 'Placeholder'],
                'min' => ['type' => 'number', 'label' => 'Минимум символов'],
                'required' => ['type' => 'boolean', 'label' => 'Обязательное'],
            ]],
            'blacklist' => ['type' => 'tags', 'label' => 'Стоп-слова'],
            'before_after' => ['type' => 'json', 'label' => 'Шкалы до/после'],
        ];
    }

    public function score(array $config, array $response): EngineResult
    {
        $fields = $response['fields'] ?? [];
        $total = 0;
        $earned = 0;
        $metrics = [];
        $blacklistHits = [];
        $blacklist = $config['blacklist'] ?? [];

        foreach ($config['fields'] ?? [] as $field) {
            $key = $field['key'];
            $required = (bool) ($field['required'] ?? true);
            $min = (int) ($field['min'] ?? 10);
            $value = trim((string) ($fields[$key] ?? ''));

            $metrics['len_'.$key] = mb_strlen($value);

            if (! $required) {
                continue;
            }

            $total++;
            if ($value !== '' && mb_strlen($value) >= $min) {
                $earned++;
            }

            foreach ($blacklist as $word) {
                if ($word !== '' && mb_stripos($value, $word) !== false) {
                    $blacklistHits[] = $word;
                }
            }
        }

        $penalty = min(count(array_unique($blacklistHits)) * 10, 30);
        $percent = $total > 0 ? max(0, round($earned / $total * 100 - $penalty, 1)) : 100.0;

        $beforeAfter = $config['before_after'] ?? null;
        if (is_array($beforeAfter) && isset($beforeAfter['before_key'], $beforeAfter['after_key'])) {
            $before = (int) ($fields[$beforeAfter['before_key']] ?? 0);
            $after = (int) ($fields[$beforeAfter['after_key']] ?? 0);
            $metrics['before'] = $before;
            $metrics['after'] = $after;
            $metrics['delta'] = $after - $before;
        }

        $feedback = [];
        if ($blacklistHits !== []) {
            $feedback[] = 'В тексте есть формулировки, которые усиливают защиту: «'.implode('», «', array_unique($blacklistHits)).'». Попробуйте заменить их на факт и просьбу.';
        }

        if ($percent >= 80) {
            $feedback[] = $config['success'] ?? 'Хорошо: высказывание опирается на факт, чувство и конкретную просьбу.';
        } else {
            $feedback[] = $config['retry'] ?? 'Добавьте конкретики: что произошло, что вы почувствовали и что именно просите сделать.';
        }

        return new EngineResult(
            percent: $percent,
            metrics: $metrics,
            feedback: $feedback,
        );
    }
}
