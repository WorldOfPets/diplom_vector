<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Module;
use App\Models\Trainer;
use App\Models\TrainerAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainerResultChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_radar_scale_fits_share_values_instead_of_a_hundred(): void
    {
        [$user, $module] = $this->makeModule();

        $trainer = Trainer::query()->create([
            'module_id' => $module->id,
            'slug' => 'motivation-tags-test',
            'title' => 'Мотивационные теги',
            'subtitle' => 'Тестовый тренажёр',
            'engine' => 'tag_weight',
            'config' => [
                'categories' => [
                    ['key' => 'material', 'label' => 'Материальные', 'color' => '#F59E0B'],
                    ['key' => 'cognitive', 'label' => 'Познавательные', 'color' => '#7C5CFF'],
                    ['key' => 'social', 'label' => 'Социальные', 'color' => '#2BB673'],
                ],
            ],
            'settings' => ['threshold' => 0],
            'points' => 15,
            'duration_min' => 5,
            'sort' => 1,
            'is_published' => true,
        ]);

        $attempt = $this->makeAttempt($user, $trainer, [
            'motive_material' => 29,
            'motive_cognitive' => 45,
            'motive_social' => 11,
        ]);

        $response = $this->actingAs($user)->get(route('trainers.result', $attempt));

        $response->assertOk()
            ->assertSee('в процентах · шкала 0–50')
            ->assertSee('45%');
    }

    public function test_point_based_radar_lists_values_out_of_ten(): void
    {
        [$user, $module] = $this->makeModule();

        $trainer = Trainer::query()->create([
            'module_id' => $module->id,
            'slug' => 'wheel-balance-test',
            'title' => 'Колесо баланса',
            'subtitle' => 'Тестовый тренажёр',
            'engine' => 'profile_sliders',
            'config' => [
                'min' => 0,
                'max' => 10,
                'areas' => [
                    ['key' => 'study', 'label' => 'Учёба', 'color' => '#2D5BFF'],
                    ['key' => 'health', 'label' => 'Здоровье', 'color' => '#0EA5A4'],
                    ['key' => 'hobby', 'label' => 'Хобби', 'color' => '#DB2777'],
                ],
            ],
            'settings' => ['threshold' => 0],
            'points' => 15,
            'duration_min' => 6,
            'sort' => 1,
            'is_published' => true,
        ]);

        $attempt = $this->makeAttempt($user, $trainer, [
            'area_study' => 7,
            'area_health' => 4,
            'area_hobby' => 9,
        ]);

        $response = $this->actingAs($user)->get(route('trainers.result', $attempt));

        $response->assertOk()
            ->assertSee('в баллах 0–10')
            ->assertSee('9 / 10')
            ->assertDontSee('9%');
    }

    /**
     * @return array{0: User, 1: Module}
     */
    private function makeModule(): array
    {
        $module = Module::query()->create([
            'slug' => 'motivation-test',
            'title' => 'Мотивация',
            'subtitle' => 'Тестовый модуль',
            'sort' => 1,
            'is_published' => true,
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Student,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        return [$user, $module];
    }

    /**
     * @param  array<string, float|int>  $metrics
     */
    private function makeAttempt(User $user, Trainer $trainer, array $metrics): TrainerAttempt
    {
        return TrainerAttempt::query()->create([
            'user_id' => $user->id,
            'trainer_id' => $trainer->id,
            'trainer_version' => 1,
            'status' => 'completed',
            'response' => [],
            'config_snapshot' => $trainer->config,
            'metrics' => $metrics,
            'score' => 100,
            'max_score' => 100,
            'percent' => 100,
            'passed' => true,
            'feedback' => null,
            'duration_sec' => 60,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);
    }
}
