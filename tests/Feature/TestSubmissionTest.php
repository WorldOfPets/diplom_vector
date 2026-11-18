<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\TestItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_ability_test_is_scored_and_saved(): void
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

        $test = Test::query()->create([
            'slug' => 'ei-test',
            'title' => 'Тест ЭИ',
            'kind' => 'ei_express',
            'time_limit_min' => 15,
            'is_active' => true,
            'version' => 1,
        ]);

        $single = TestItem::query()->create([
            'test_id' => $test->id,
            'branch' => 'perception',
            'type' => 'single',
            'prompt' => 'Что чувствует человек?',
            'config' => ['options' => [
                ['id' => 'o1', 'label' => 'Спокойствие'],
                ['id' => 'o2', 'label' => 'Тревога'],
            ]],
            'key_data' => ['weights' => ['o2' => 1.0]],
            'sort' => 1,
            'is_active' => true,
        ]);

        $bestWorst = TestItem::query()->create([
            'test_id' => $test->id,
            'branch' => 'managing',
            'type' => 'best_worst',
            'prompt' => 'Выберите лучшую и худшую реакцию',
            'config' => ['options' => [
                ['id' => 'o1', 'label' => 'Промолчать'],
                ['id' => 'o2', 'label' => 'Спокойно уточнить'],
            ]],
            'key_data' => ['best' => 'o2', 'worst' => 'o1'],
            'sort' => 2,
            'is_active' => true,
        ]);

        $attempt = TestAttempt::query()->create([
            'test_id' => $test->id,
            'user_id' => $user->id,
            'form' => 'A',
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(route('tests.submit', $attempt), [
            'answers' => [
                $single->id => ['value' => 'o2'],
                $bestWorst->id => ['best' => 'o2', 'worst' => 'o1'],
            ],
        ]);

        $response->assertOk()->assertJsonStructure(['redirect']);

        $attempt->refresh();

        $this->assertSame('completed', $attempt->status);
        $this->assertSame(100.0, (float) $attempt->overall);
        $this->assertSame(100.0, (float) $attempt->scores['perception']);
        $this->assertSame(100.0, (float) $attempt->scores['managing']);
        $this->assertDatabaseCount('test_responses', 2);
    }

    public function test_motivation_test_reverses_negative_items(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'group_id' => Group::query()->create([
                'name' => 'ИС-22',
                'code' => 'IS-22',
                'course' => 3,
            ])->id,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $test = Test::query()->create([
            'slug' => 'motivation-test',
            'title' => 'Мотивация',
            'kind' => 'motivation',
            'is_active' => true,
            'version' => 1,
            'config' => ['subscales' => ['internal' => 'Внутренняя']],
        ]);

        $item = TestItem::query()->create([
            'test_id' => $test->id,
            'branch' => 'internal',
            'type' => 'motivation',
            'prompt' => 'Учёба — пустая трата времени',
            'config' => ['subscale' => 'internal', 'reverse' => true, 'min' => 1, 'max' => 5],
            'key_data' => ['reverse' => true],
            'sort' => 1,
            'is_active' => true,
        ]);

        $attempt = TestAttempt::query()->create([
            'test_id' => $test->id,
            'user_id' => $user->id,
            'form' => 'A',
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $this->actingAs($user)->postJson(route('tests.submit', $attempt), [
            'answers' => [$item->id => ['value' => 1]],
        ])->assertOk();

        $attempt->refresh();

        $this->assertSame(100.0, (float) $attempt->overall);
        $this->assertSame(100.0, (float) $attempt->scores['internal']);
    }
}
