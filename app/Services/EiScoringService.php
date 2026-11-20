<?php

namespace App\Services;

use App\Enums\EiBranch;
use App\Models\Test;
use App\Models\TestItem;

class EiScoringService
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
        $branchTotals = [];
        $branchMax = [];
        $responses = [];

        foreach ($items as $item) {
            $response = $answers[$item->id] ?? [];
            [$score, $max] = $this->scoreItem($item, $response);

            $weight = max(0.1, (float) ($item->weight ?? 1));
            $score *= $weight;
            $max *= $weight;

            $responses[$item->id] = [
                'response' => $response,
                'score' => $score,
                'max' => $max,
                'ms' => isset($response['ms']) ? max(0, min(86400000, (int) $response['ms'])) : null,
            ];

            $branch = $item->branch;
            $branchTotals[$branch] = ($branchTotals[$branch] ?? 0) + $score;
            $branchMax[$branch] = ($branchMax[$branch] ?? 0) + $max;
        }

        $scores = [];
        foreach ($branchTotals as $branch => $total) {
            $scores[$branch] = $branchMax[$branch] > 0
                ? round($total / $branchMax[$branch] * 100, 1)
                : 0.0;
        }

        $present = array_values(array_filter(
            $scores,
            fn (string $branch): bool => in_array($branch, array_map(fn (EiBranch $case): string => $case->value, EiBranch::eiBranches()), true),
            ARRAY_FILTER_USE_KEY,
        ));

        $overall = $present !== [] ? round(array_sum($present) / count($present), 1) : 0.0;

        return [
            'responses' => $responses,
            'scores' => $scores,
            'overall' => $overall,
            'level' => $this->level($overall, $test->config['levels'] ?? []),
        ];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array{0: float, 1: float}
     */
    private function scoreItem(TestItem $item, array $response): array
    {
        $key = $item->key_data ?? [];
        $type = $item->type;

        return match ($type) {
            'single' => $this->scoreSingle($key, $response),
            'multi' => $this->scoreMulti($key, $response),
            'scene', 'image_choice' => $this->scoreScene($key, $response),
            'best_worst' => $this->scoreBestWorst($key, $response),
            'ordering' => $this->scoreOrdering($key, $response),
            'matching' => $this->scoreMatching($key, $response),
            'scale' => $this->scoreScale($key, $response),
            default => [0.0, 1.0],
        };
    }

    /**
     * @param  array<string, mixed>  $key
     * @param  array<string, mixed>  $response
     * @return array{0: float, 1: float}
     */
    private function scoreSingle(array $key, array $response): array
    {
        $value = $response['value'] ?? null;
        $weights = $key['weights'] ?? [];

        if ($weights !== []) {
            $max = max(array_map('floatval', $weights)) ?: 1.0;

            return [(float) ($weights[$value] ?? 0), $max];
        }

        return [$value === ($key['correct'] ?? null) ? 1.0 : 0.0, 1.0];
    }

    /**
     * @param  array<string, mixed>  $key
     * @param  array<string, mixed>  $response
     * @return array{0: float, 1: float}
     */
    private function scoreMulti(array $key, array $response): array
    {
        $selected = array_values(array_filter($response['values'] ?? [], 'is_string'));
        $weights = $key['weights'] ?? [];
        $penalty = (float) ($key['penalty'] ?? 0.15);
        $max = (float) ($key['max'] ?? array_sum(array_map('floatval', $weights)));

        $score = 0.0;
        foreach ($selected as $id) {
            $score += (float) ($weights[$id] ?? 0);
            if (! isset($weights[$id])) {
                $score -= $penalty;
            }
        }

        return [max(0, min($max, $score)), $max > 0 ? $max : 1.0];
    }

    /**
     * @param  array<string, mixed>  $key
     * @param  array<string, mixed>  $response
     * @return array{0: float, 1: float}
     */
    private function scoreScene(array $key, array $response): array
    {
        $selected = array_values(array_filter($response['emotions'] ?? $response['values'] ?? [], 'is_string'));
        $weights = $key['emotions'] ?? $key['weights'] ?? [];
        $penalty = (float) ($key['penalty'] ?? 0.2);
        $max = (float) ($key['max'] ?? array_sum(array_map('floatval', $weights)));

        $score = 0.0;
        foreach ($selected as $id) {
            $score += (float) ($weights[$id] ?? 0);
            if (! isset($weights[$id])) {
                $score -= $penalty;
            }
        }

        $score = max(0, min($max, $score));

        return [$score, $max > 0 ? $max : 1.0];
    }

    /**
     * @param  array<string, mixed>  $key
     * @param  array<string, mixed>  $response
     * @return array{0: float, 1: float}
     */
    private function scoreBestWorst(array $key, array $response): array
    {
        $best = $response['best'] ?? null;
        $worst = $response['worst'] ?? null;

        if ($best === null || $worst === null || $best === $worst) {
            return [0.0, 1.0];
        }

        $score = 0.0;
        if ($best === ($key['best'] ?? null)) {
            $score += 0.5;
        }
        if ($worst === ($key['worst'] ?? null)) {
            $score += 0.5;
        }
        if ($best === ($key['worst'] ?? null) && $worst === ($key['best'] ?? null)) {
            $score = max(0, $score - 0.5);
        }

        return [$score, 1.0];
    }

    /**
     * @param  array<string, mixed>  $key
     * @param  array<string, mixed>  $response
     * @return array{0: float, 1: float}
     */
    private function scoreOrdering(array $key, array $response): array
    {
        if (array_key_exists('orderTouched', $response) && $response['orderTouched'] !== true) {
            return [0.0, 1.0];
        }

        $target = array_values($key['order'] ?? []);
        $order = array_values(array_filter($response['order'] ?? [], 'is_string'));

        if ($target === [] || $order === []) {
            return [0.0, 1.0];
        }

        $positions = array_flip($target);
        $n = count($target);
        $exact = 0;

        foreach ($order as $index => $id) {
            if (($positions[$id] ?? null) === $index) {
                $exact++;
            }
        }

        return [$exact / $n, 1.0];
    }

    /**
     * @param  array<string, mixed>  $key
     * @param  array<string, mixed>  $response
     * @return array{0: float, 1: float}
     */
    private function scoreMatching(array $key, array $response): array
    {
        $pairs = $key['pairs'] ?? [];
        $answers = $response['pairs'] ?? [];
        $total = count($pairs);

        if ($total === 0) {
            return [0.0, 1.0];
        }

        $correct = 0;
        foreach ($pairs as $left => $right) {
            if (($answers[$left] ?? null) === $right) {
                $correct++;
            }
        }

        return [$correct / $total, 1.0];
    }

    /**
     * @param  array<string, mixed>  $key
     * @param  array<string, mixed>  $response
     * @return array{0: float, 1: float}
     */
    private function scoreScale(array $key, array $response): array
    {
        $value = (float) ($response['value'] ?? 0);
        $min = (float) ($key['min'] ?? 0);
        $max = (float) ($key['max'] ?? 10);
        $target = $key['target'] ?? null;

        if ($target !== null) {
            $distance = abs($value - (float) $target);
            $range = max($max - $min, 1);

            return [max(0, 1 - $distance / $range), 1.0];
        }

        return [max(0, min(1, ($value - $min) / max($max - $min, 1))), 1.0];
    }

    /**
     * @param  array<string, int|float>  $bands
     */
    public function level(float $percent, array $bands = []): string
    {
        return match (true) {
            $percent >= ($bands['high'] ?? 85) => 'high',
            $percent >= ($bands['above_average'] ?? 70) => 'above_average',
            $percent >= ($bands['average'] ?? 55) => 'average',
            $percent >= ($bands['below_average'] ?? 40) => 'below_average',
            default => 'low',
        };
    }
}
