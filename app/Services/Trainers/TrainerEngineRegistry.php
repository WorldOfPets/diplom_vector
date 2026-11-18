<?php

namespace App\Services\Trainers;

use App\Services\Trainers\Engines\AllocationEngine;
use App\Services\Trainers\Engines\BestWorstEngine;
use App\Services\Trainers\Engines\BreathingEngine;
use App\Services\Trainers\Engines\CameraEngine;
use App\Services\Trainers\Engines\CategorizeEngine;
use App\Services\Trainers\Engines\ConstructorEngine;
use App\Services\Trainers\Engines\GoNoGoEngine;
use App\Services\Trainers\Engines\ImageChoiceEngine;
use App\Services\Trainers\Engines\MatchingEngine;
use App\Services\Trainers\Engines\ProfileSlidersEngine;
use App\Services\Trainers\Engines\RankingEngine;
use App\Services\Trainers\Engines\ReflectionEngine;
use App\Services\Trainers\Engines\ScenarioEngine;
use App\Services\Trainers\Engines\ShieldEngine;
use App\Services\Trainers\Engines\TagWeightEngine;
use App\Services\Trainers\Engines\TimelineEngine;
use InvalidArgumentException;

class TrainerEngineRegistry
{
    /**
     * @var array<string, class-string<TrainerEngine>>
     */
    private array $engines = [
        'ranking' => RankingEngine::class,
        'allocation' => AllocationEngine::class,
        'tag_weight' => TagWeightEngine::class,
        'reflection' => ReflectionEngine::class,
        'breathing' => BreathingEngine::class,
        'go_no_go' => GoNoGoEngine::class,
        'constructor' => ConstructorEngine::class,
        'matching' => MatchingEngine::class,
        'image_choice' => ImageChoiceEngine::class,
        'best_worst' => BestWorstEngine::class,
        'scenario' => ScenarioEngine::class,
        'profile_sliders' => ProfileSlidersEngine::class,
        'camera' => CameraEngine::class,
        'categorize' => CategorizeEngine::class,
        'shield' => ShieldEngine::class,
        'timeline' => TimelineEngine::class,
    ];

    /**
     * @return array<string, string>
     */
    public function options(): array
    {
        $options = [];

        foreach ($this->engines as $key => $class) {
            $options[$key] = app($class)->label();
        }

        return $options;
    }

    public function get(string $key): TrainerEngine
    {
        $class = $this->engines[$key] ?? null;

        if ($class === null) {
            throw new InvalidArgumentException("Unknown trainer engine [{$key}].");
        }

        return app($class);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->engines);
    }
}
