<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Module;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainerTextQualityTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_text_with_keyboard_mash_is_rejected(): void
    {
        [$user, $trainer] = $this->makeReflectionTrainer();

        $response = $this->actingAs($user)->postJson(route('trainers.submit', $trainer), [
            'response' => [
                'card' => 'key',
                'texts' => ['step' => 'билеберда фывап'],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('случайный набор букв', (string) $response->json('message'));
    }

    public function test_free_text_with_real_words_is_saved(): void
    {
        [$user, $trainer] = $this->makeReflectionTrainer();

        $response = $this->actingAs($user)->postJson(route('trainers.submit', $trainer), [
            'response' => [
                'card' => 'key',
                'texts' => ['step' => 'Поговорю с куратором в понедельник'],
            ],
        ]);

        $response->assertOk()->assertJsonStructure(['redirect']);

        $this->assertDatabaseCount('trainer_attempts', 1);
    }

    /**
     * @return array{0: User, 1: Trainer}
     */
    private function makeReflectionTrainer(): array
    {
        $module = Module::query()->create([
            'slug' => 'understand-self-text',
            'title' => 'Понимаю себя',
            'subtitle' => 'Тестовый модуль',
            'sort' => 1,
            'is_published' => true,
        ]);

        $trainer = Trainer::query()->create([
            'module_id' => $module->id,
            'slug' => 'wise-man-text',
            'title' => 'Встреча с мудрецом',
            'subtitle' => 'Тестовый тренажёр',
            'engine' => 'reflection',
            'config' => [
                'title' => 'Встреча с мудрецом',
                'prompt' => 'Прочитайте сценарий.',
                'cards' => [
                    ['id' => 'key', 'title' => 'Ключ'],
                ],
                'texts' => [
                    ['key' => 'step', 'label' => 'Первый шаг', 'placeholder' => ''],
                ],
            ],
            'settings' => ['threshold' => 0],
            'points' => 15,
            'duration_min' => 6,
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
}
