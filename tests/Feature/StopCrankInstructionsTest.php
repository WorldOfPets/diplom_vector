<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Module;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StopCrankInstructionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_stop_crank_explains_the_rule_flip_before_the_game(): void
    {
        $module = Module::query()->create([
            'slug' => 'manage-state-test',
            'title' => 'Управляю состоянием',
            'subtitle' => 'Тестовый модуль',
            'sort' => 1,
            'is_published' => true,
        ]);

        $trainer = Trainer::query()->create([
            'module_id' => $module->id,
            'slug' => 'stop-crank-test',
            'title' => 'Стоп-кран 2.0',
            'subtitle' => 'Тестовый тренажёр',
            'engine' => 'go_no_go',
            'config' => [
                'title' => 'Стоп-кран 2.0',
                'prompt' => 'Нажимайте на «Спокойно» и не нажимайте на «Триггер».',
                'trials' => 30,
                'response_window_ms' => 800,
                'rule_flip_after' => 15,
                'adaptive' => true,
                'go_label' => 'Спокойно',
                'nogo_label' => 'Триггер',
            ],
            'settings' => ['threshold' => 0],
            'points' => 20,
            'duration_min' => 5,
            'sort' => 1,
            'is_published' => true,
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Student,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $response = $this->actingAs($user)->get(route('trainers.show', $trainer));

        $response->assertOk()
            ->assertSee('Как играть')
            ->assertSee('После 15-й пробы правило меняется')
            ->assertSee('нажимать нужно на «Триггер»', false)
            ->assertSee('x-text="ruleText"', false);
    }
}
