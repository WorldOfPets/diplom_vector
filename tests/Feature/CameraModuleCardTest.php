<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\CameraProgress;
use App\Models\Module;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CameraModuleCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_card_shows_partial_camera_progress_and_continue_button(): void
    {
        [$user, $module] = $this->makeModuleWithCameraTrainer();

        foreach (['happy', 'sad', 'angry', 'surprised', 'fearful'] as $emotion) {
            CameraProgress::query()->create([
                'user_id' => $user->id,
                'emotion' => $emotion,
                'confidence' => 90,
                'duration_ms' => 2000,
                'attempts' => 1,
                'completed_at' => now(),
            ]);
        }

        $response = $this->actingAs($user)->get(route('modules.show', $module));

        $response->assertOk()
            ->assertSee('5 из 7 эмоций')
            ->assertSee('Продолжить')
            ->assertSee('Осталось показать: Отвращение, Нейтрально');
    }

    public function test_module_card_keeps_start_button_without_camera_progress(): void
    {
        [$user, $module] = $this->makeModuleWithCameraTrainer();

        $response = $this->actingAs($user)->get(route('modules.show', $module));

        $response->assertOk()
            ->assertSee('0 из 7 эмоций')
            ->assertSee('Начать')
            ->assertDontSee('Продолжить');
    }

    /**
     * @return array{0: User, 1: Module}
     */
    private function makeModuleWithCameraTrainer(): array
    {
        $module = Module::query()->create([
            'slug' => 'understand-others-test',
            'title' => 'Понимаю других',
            'subtitle' => 'Тестовый модуль',
            'sort' => 1,
            'is_published' => true,
        ]);

        Trainer::query()->create([
            'module_id' => $module->id,
            'slug' => 'mimic-test',
            'title' => 'Мимика',
            'subtitle' => 'Семь мимических паттернов.',
            'engine' => 'camera',
            'config' => [
                'targets' => [
                    ['id' => 'happy', 'label' => 'Радость', 'threshold' => 80],
                    ['id' => 'sad', 'label' => 'Грусть', 'threshold' => 80],
                    ['id' => 'angry', 'label' => 'Злость', 'threshold' => 80],
                    ['id' => 'surprised', 'label' => 'Удивление', 'threshold' => 80],
                    ['id' => 'fearful', 'label' => 'Страх', 'threshold' => 80],
                    ['id' => 'disgusted', 'label' => 'Отвращение', 'threshold' => 80],
                    ['id' => 'neutral', 'label' => 'Нейтрально', 'threshold' => 80],
                ],
            ],
            'settings' => ['threshold' => 80],
            'points' => 20,
            'duration_min' => 5,
            'sort' => 1,
            'is_published' => true,
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Student,
            'consent_at' => now(),
            'consent_version' => '1.0',
            'camera_consent_at' => now(),
        ]);

        return [$user, $module];
    }
}
