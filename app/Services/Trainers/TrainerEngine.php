<?php

namespace App\Services\Trainers;

interface TrainerEngine
{
    public function key(): string;

    public function label(): string;

    /**
     * @return array<string, mixed>
     */
    public function schema(): array;

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function publicConfig(array $config): array;

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $response
     */
    public function score(array $config, array $response): EngineResult;
}
