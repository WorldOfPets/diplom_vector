<?php

namespace App\Services;

use App\Models\Test;

class MotivationScoringService
{
    /**
     * @param  array<int, array<string, mixed>>  $answers
     * @return array{
     *     responses: array<int, array{response: array<string, mixed>, score: float, max: float, ms: int|null}>,
     *     scores: array<string, float>,
     *     overall: float,
     *     level: string
     * }
     */
    public function score(Test $test, array $answers): array
    {
        $items = $test->items()->where('is_active', true)->get();
        $responses = [];
        $subscales = [];
        $all = [];

        foreach ($items as $item) {
            $response = $answers[$item->id] ?? [];
            $value = isset($response['value']) ? (int) $response['value'] : null;

            if ($value === null) {
                continue;
            }

            $value = max(1, min(5, $value));
            $reverse = (bool) ($item->key_data['reverse'] ?? $item->config['reverse'] ?? false);
            $normalized = $reverse ? (5 - $value) / 4 : ($value - 1) / 4;

            $subscale = $item->config['subscale'] ?? 'motivation';
            $subscales[$subscale][] = $normalized;
            $all[] = $normalized;

            $responses[$item->id] = [
                'response' => $response,
                'score' => $normalized,
                'max' => 1.0,
                'ms' => isset($response['ms']) ? (int) $response['ms'] : null,
            ];
        }

        $scores = [];
        foreach ($subscales as $subscale => $values) {
            $scores[$subscale] = round(array_sum($values) / count($values) * 100, 1);
        }

        $selfDetermined = array_merge($subscales['internal'] ?? [], $subscales['professional'] ?? []);
        $overallBase = $selfDetermined !== [] ? $selfDetermined : $all;
        $overall = $overallBase !== [] ? round(array_sum($overallBase) / count($overallBase) * 100, 1) : 0.0;

        return [
            'responses' => $responses,
            'scores' => $scores,
            'overall' => $overall,
            'level' => $this->level($overall, $test->config['levels'] ?? []),
        ];
    }

    /**
     * @param  array<string, int|float>  $bands
     */
    public function level(float $percent, array $bands = []): string
    {
        return match (true) {
            $percent >= ($bands['high'] ?? 80) => 'high',
            $percent >= ($bands['above_average'] ?? 60) => 'above_average',
            $percent >= ($bands['average'] ?? 40) => 'average',
            $percent >= ($bands['below_average'] ?? 20) => 'below_average',
            default => 'low',
        };
    }
}
