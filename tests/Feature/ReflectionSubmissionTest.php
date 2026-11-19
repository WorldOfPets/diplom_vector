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

class ReflectionSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_reflection_is_saved_for_the_attempt_owner(): void
    {
        [$user, $attempt] = $this->makeAttempt();

        $response = $this->actingAs($user)->post(route('reflections.store', $attempt), [
            'choice' => 'suitcase',
            'note' => 'Забираю конкретный шаг.',
        ]);

        $response->assertRedirect(route('trainers.result', $attempt));
        $this->assertDatabaseHas('reflections', [
            'user_id' => $user->id,
            'trainer_attempt_id' => $attempt->id,
            'choice' => 'suitcase',
        ]);
    }

    public function test_reflection_is_forbidden_for_another_user(): void
    {
        [, $attempt] = $this->makeAttempt();
        $other = $this->makeUser('ИС-52', 'IS-52');

        $this->actingAs($other)->post(route('reflections.store', $attempt), [
            'choice' => 'basket',
        ])->assertForbidden();
    }

    /**
     * @return array{0: User, 1: TrainerAttempt}
     */
    private function makeAttempt(): array
    {
        $user = $this->makeUser('ИС-51', 'IS-51');

        $module = Module::query()->create([
            'slug' => 'reflection-test',
            'title' => 'Рефлексия',
            'subtitle' => 'Тестовый модуль',
            'sort' => 1,
            'is_published' => true,
        ]);

        $trainer = Trainer::query()->create([
            'module_id' => $module->id,
            'slug' => 'reflection-test',
            'title' => 'Рефлексия',
            'subtitle' => 'Тестовый тренажёр',
            'engine' => 'reflection',
            'config' => [],
            'settings' => ['threshold' => 0],
            'points' => 10,
            'duration_min' => 5,
            'sort' => 1,
            'is_published' => true,
        ]);

        $attempt = TrainerAttempt::query()->create([
            'user_id' => $user->id,
            'trainer_id' => $trainer->id,
            'trainer_version' => 1,
            'status' => 'completed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'percent' => 100,
        ]);

        return [$user, $attempt];
    }

    private function makeUser(string $groupName, string $groupCode): User
    {
        $group = Group::query()->create([
            'name' => $groupName,
            'code' => $groupCode,
            'course' => 2,
        ]);

        return User::factory()->create([
            'role' => UserRole::Student,
            'group_id' => $group->id,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);
    }
}
