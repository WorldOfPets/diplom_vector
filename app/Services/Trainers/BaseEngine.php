<?php

namespace App\Services\Trainers;

abstract class BaseEngine implements TrainerEngine
{
    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function publicConfig(array $config): array
    {
        unset($config['scoring'], $config['key']);

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<array<string, mixed>>
     */
    protected function items(array $config): array
    {
        return array_values($config['items'] ?? []);
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, string>
     */
    protected function categoryLabels(array $config): array
    {
        $labels = [];

        foreach ($config['categories'] ?? [] as $category) {
            $labels[$category['key']] = $category['label'];
        }

        return $labels;
    }

    /**
     * @param  array<string, float>  $values
     * @return array<string, float>
     */
    protected function toPercents(array $values): array
    {
        $total = array_sum($values);

        if ($total <= 0) {
            return array_map(fn (): float => 0.0, $values);
        }

        return array_map(fn (float $value): float => round($value / $total * 100, 1), $values);
    }
}
