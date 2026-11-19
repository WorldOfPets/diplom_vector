<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\Module;
use App\Models\Trainer;
use App\Models\TrainerAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaleTrainerAttemptTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_in_progress_attempt_is_reset_on_open_and_can_be_submitted(): void
    {
        $group = Group::query()->create([
            'name' => 'ИС-61',
            'code' => 'IS-61',
            'course' => 3,
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Student,
            'group_id' => $group->id,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $module = Module::query()->create([
            'slug' => 'stale-test',
            'title' => 'Версии',
            'subtitle' => 'Тестовый модуль',
            'sort' => 1,
            'is_published' => true,
        ]);

        $trainer = Trainer::query()->create([
            'module_id' => $module->id,
            'slug' => 'stale-test',
            'title' => 'Версии',
            'subtitle' => 'Тестовый тренажёр',
            'engine' => 'tag_weight',
            'config' => [
                'prompt' => 'Выберите теги',
                'max_tags' => 8,
                'tags' => [['id' => 'income', 'label' => 'Доход', 'category' => 'material']],
                'categories' => [['key' => 'material', 'label' => 'Материальные', 'color' => '#F59E0B']],
            ],
            'settings' => ['threshold' => 0],
            'points' => 10,
            'duration_min' => 5,
            'sort' => 1,
            'is_published' => true,
            'version' => 2,
        ]);

        $attempt = TrainerAttempt::query()->create([
            'user_id' => $user->id,
            'trainer_id' => $trainer->id,
            'trainer_version' => 1,
            'status' => 'in_progress',
            'response' => ['tags' => ['income' => 3]],
            'started_at' => now()->subHour(),
        ]);

        $this->actingAs($user)->get(route('trainers.show', $trainer))->assertOk();

        $attempt->refresh();

        $this->assertSame(2, $attempt->trainer_version);
        $this->assertNull($attempt->response);

        $this->actingAs($user)->postJson(route('trainers.submit', $trainer), [
            'response' => ['tags' => ['income' => 3]],
            'attempt_id' => $attempt->id,
        ])->assertOk();

        $this->assertSame('completed', $attempt->refresh()->status);
    }
}
