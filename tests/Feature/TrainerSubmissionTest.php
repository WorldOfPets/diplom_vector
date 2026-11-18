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

class TrainerSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_weight_trainer_is_scored_and_saved(): void
    {
        $group = Group::query()->create([
            'name' => 'ИС-21',
            'code' => 'IS-21',
            'course' => 2,
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Student,
            'group_id' => $group->id,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $module = Module::query()->create([
            'slug' => 'motivation-test',
            'title' => 'Мотивация',
            'subtitle' => 'Тестовый модуль',
            'sort' => 1,
            'is_published' => true,
        ]);

        $trainer = Trainer::query()->create([
            'module_id' => $module->id,
            'slug' => 'tags-test',
            'title' => 'Теги',
            'subtitle' => 'Тестовый тренажёр',
            'engine' => 'tag_weight',
            'config' => [
                'prompt' => 'Выберите теги',
                'max_tags' => 8,
                'tags' => [
                    ['id' => 'income', 'label' => 'Доход', 'category' => 'material'],
                    ['id' => 'learning', 'label' => 'Обучение', 'category' => 'cognitive'],
                ],
                'categories' => [
                    ['key' => 'material', 'label' => 'Материальные', 'color' => '#F59E0B'],
                    ['key' => 'cognitive', 'label' => 'Познавательные', 'color' => '#7C5CFF'],
                ],
            ],
            'settings' => ['threshold' => 0],
            'points' => 10,
            'duration_min' => 5,
            'sort' => 1,
            'is_published' => true,
        ]);

        $response = $this->actingAs($user)->postJson(route('trainers.submit', $trainer), [
            'response' => [
                'tags' => ['income' => 3, 'learning' => 2],
            ],
        ]);

        $response->assertOk()->assertJsonStructure(['redirect']);

        $attempt = TrainerAttempt::query()->firstOrFail();

        $this->assertSame('completed', $attempt->status);
        $this->assertSame(100.0, (float) $attempt->percent);
        $this->assertTrue($attempt->passed);
        $this->assertSame(60.0, (float) ($attempt->metrics['motive_material'] ?? 0));
        $this->assertSame(40.0, (float) ($attempt->metrics['motive_cognitive'] ?? 0));
    }
}
