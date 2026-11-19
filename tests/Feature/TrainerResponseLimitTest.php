<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\Module;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainerResponseLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_oversized_trainer_response_is_rejected_before_scoring(): void
    {
        $group = Group::query()->create([
            'name' => 'ИС-41',
            'code' => 'IS-41',
            'course' => 3,
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Student,
            'group_id' => $group->id,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $module = Module::query()->create([
            'slug' => 'limit-test',
            'title' => 'Лимиты',
            'subtitle' => 'Тестовый модуль',
            'sort' => 1,
            'is_published' => true,
        ]);

        $trainer = Trainer::query()->create([
            'module_id' => $module->id,
            'slug' => 'limit-test',
            'title' => 'Лимиты',
            'subtitle' => 'Тестовый тренажёр',
            'engine' => 'reflection',
            'config' => [],
            'settings' => ['threshold' => 0],
            'points' => 10,
            'duration_min' => 5,
            'sort' => 1,
            'is_published' => true,
        ]);

        $this->actingAs($user)
            ->postJson(route('trainers.submit', $trainer), [
                'response' => ['strokes' => str_repeat('a', 300000)],
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('trainer_attempts', 0);
    }
}
