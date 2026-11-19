<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Module;
use App\Models\Trainer;
use App\Models\TrainerAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainerProgressUnitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_result_shows_units_according_to_the_grading(): void
    {
        [$user, $trainer] = $this->makeTrainer();

        $low = $this->makeAttempt($user, $trainer, 45.0);
        $middle = $this->makeAttempt($user, $trainer, 60.0);
        $high = $this->makeAttempt($user, $trainer, 95.0);

        $this->actingAs($user)
            ->get(route('trainers.result', $low))
            ->assertOk()
            ->assertSee('0 единиц прогресса');

        $this->actingAs($user)
            ->get(route('trainers.result', $middle))
            ->assertOk()
            ->assertSee('1 единица прогресса');

        $this->actingAs($user)
            ->get(route('trainers.result', $high))
            ->assertOk()
            ->assertSee('2 единицы прогресса');
    }

    /**
     * @return array{0: User, 1: Trainer}
     */
    private function makeTrainer(): array
    {
        $module = Module::query()->create([
            'slug' => 'understand-self-units',
            'title' => 'Понимаю себя',
            'subtitle' => 'Тестовый модуль',
            'sort' => 1,
            'is_published' => true,
        ]);

        $trainer = Trainer::query()->create([
            'module_id' => $module->id,
            'slug' => 'card-of-day-units',
            'title' => 'Карта дня',
            'subtitle' => 'Тестовый тренажёр',
            'engine' => 'reflection',
            'config' => ['prompt' => 'Выберите карту'],
            'settings' => ['threshold' => 0],
            'points' => 10,
            'duration_min' => 5,
            'sort' => 1,
            'is_published' => true,
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Student,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        return [$user, $trainer];
    }

    private function makeAttempt(User $user, Trainer $trainer, float $percent): TrainerAttempt
    {
        return TrainerAttempt::query()->create([
            'user_id' => $user->id,
            'trainer_id' => $trainer->id,
            'trainer_version' => 1,
            'status' => 'completed',
            'response' => [],
            'config_snapshot' => $trainer->config,
            'metrics' => [],
            'score' => $percent,
            'max_score' => 100,
            'percent' => $percent,
            'passed' => $percent >= 60,
            'feedback' => null,
            'duration_sec' => 60,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);
    }
}
