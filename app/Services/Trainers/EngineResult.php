<?php

namespace App\Services\Trainers;

final class EngineResult
{
    /**
     * @param  array<string, float|int|string|null>  $metrics
     * @param  list<string>  $feedback
     */
    public function __construct(
        public readonly float $percent,
        public readonly array $metrics = [],
        public readonly array $feedback = [],
        public readonly ?string $summary = null,
    ) {}
}
