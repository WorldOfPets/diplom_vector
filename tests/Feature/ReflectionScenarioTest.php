<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Module;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReflectionScenarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_reflection_trainer_renders_the_scenario_steps(): void
    {
        $trainer = $this->makeTrainer([
            'scenario' => "Сделайте три спокойных вдоха.\nВторой шаг сценария.",
        ]);

        $response = $this->actingAs($this->makeStudent())
            ->get(route('trainers.show', $trainer));

        $response->assertOk()
            ->assertSee('Сценарий')
            ->assertSee('Сделайте три спокойных вдоха.')
            ->assertSee('Второй шаг сценария.');
    }

    public function test_reflection_trainer_without_scenario_does_not_render_the_block(): void
    {
        $trainer = $this->makeTrainer([]);

        $response = $this->actingAs($this->makeStudent())
            ->get(route('trainers.show', $trainer));

        $response->assertOk()->assertDontSee('Сценарий');
    }

    /**
     * @param  array<string, mixed>  $extraConfig
     */
    private function makeTrainer(array $extraConfig): Trainer
    {
        $module = Module::query()->create([
            'slug' => 'understand-self-test',
            'title' => 'Понимаю себя',
            'subtitle' => 'Тестовый модуль',
            'sort' => 1,
            'is_published' => true,
        ]);

        return Trainer::query()->create([
            'module_id' => $module->id,
            'slug' => 'wise-man-test',
            'title' => 'Встреча с мудрецом',
            'subtitle' => 'Тестовый тренажёр',
            'engine' => 'reflection',
            'config' => [
                'title' => 'Встреча с мудрецом',
                'prompt' => 'Прочитайте сценарий и выберите символ.',
                'cards' => [
                    ['id' => 'key', 'title' => 'Ключ', 'description' => 'Доступ к ответу'],
                ],
                'sliders_before' => [
                    ['key' => 'clarity', 'label' => 'Ясность запроса', 'min' => 0, 'max' => 10],
                ],
                ...$extraConfig,
            ],
            'settings' => ['threshold' => 0],
            'points' => 15,
            'duration_min' => 6,
            'sort' => 1,
            'is_published' => true,
        ]);
    }

    private function makeStudent(): User
    {
        return User::factory()->create([
            'role' => UserRole::Student,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);
    }
}
