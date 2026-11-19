<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\TestItem;
use App\Models\TestResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnosticsHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_shows_only_current_user_completed_attempts_in_chronological_order(): void
    {
        $user = $this->makeUser('ИС-31', 'IS-31');
        $other = $this->makeUser('ИС-32', 'IS-32');

        $test = Test::query()->create([
            'slug' => 'ei-test',
            'title' => 'Тест ЭИ',
            'kind' => 'ei_express',
            'time_limit_min' => 15,
            'is_active' => true,
            'version' => 1,
        ]);

        $first = TestAttempt::query()->create([
            'test_id' => $test->id,
            'user_id' => $user->id,
            'form' => 'A',
            'status' => 'completed',
            'scores' => ['perception' => 55.0],
            'overall' => 55.0,
            'level' => 'average',
            'started_at' => now()->subDays(3),
            'finished_at' => now()->subDays(3),
        ]);

        $second = TestAttempt::query()->create([
            'test_id' => $test->id,
            'user_id' => $user->id,
            'form' => 'A',
            'status' => 'completed',
            'scores' => ['perception' => 73.0],
            'overall' => 73.0,
            'level' => 'above_average',
            'started_at' => now()->subDay(),
            'finished_at' => now()->subDay(),
        ]);

        TestAttempt::query()->create([
            'test_id' => $test->id,
            'user_id' => $other->id,
            'form' => 'A',
            'status' => 'completed',
            'scores' => ['perception' => 99.0],
            'overall' => 99.0,
            'level' => 'high',
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        TestAttempt::query()->create([
            'test_id' => $test->id,
            'user_id' => $user->id,
            'form' => 'A',
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('tests.history', $test));

        $response->assertOk()->assertViewIs('tests.history');
        $response->assertViewHas('points', function (array $points) use ($first, $second): bool {
            return array_map(fn (array $point): int => $point['id'], $points) === [$first->id, $second->id];
        });

        $response->assertSeeText('История: Тест ЭИ');
        $response->assertSee('55%');
        $response->assertSee('73%');
        $response->assertDontSee('99%');
    }

    public function test_history_is_not_available_for_inactive_test(): void
    {
        $user = $this->makeUser('ИС-33', 'IS-33');

        $test = Test::query()->create([
            'slug' => 'archived-test',
            'title' => 'Архивный тест',
            'kind' => 'ei_express',
            'is_active' => false,
            'version' => 1,
        ]);

        $this->actingAs($user)->get(route('tests.history', $test))->assertNotFound();
    }

    public function test_result_page_warns_when_the_pace_is_too_fast(): void
    {
        $attempt = $this->makeCompletedAttemptWithTimings([900, 1200, 1500, 1800, 2100]);

        $response = $this->actingAs($attempt->user)->get(route('tests.result', $attempt));

        $response->assertOk();
        $response->assertSeeText('Очень быстрый темп');
    }

    public function test_result_page_does_not_warn_about_normal_pace(): void
    {
        $attempt = $this->makeCompletedAttemptWithTimings([6200, 7100, 8000, 9300, 10400]);

        $response = $this->actingAs($attempt->user)->get(route('tests.result', $attempt));

        $response->assertOk();
        $response->assertDontSeeText('Очень быстрый темп');
        $response->assertDontSeeText('Медианное время на задание');
    }

    public function test_result_page_does_not_warn_with_fewer_than_five_samples(): void
    {
        $attempt = $this->makeCompletedAttemptWithTimings([900, 1000, 1100, 1200]);

        $response = $this->actingAs($attempt->user)->get(route('tests.result', $attempt));

        $response->assertOk();
        $response->assertDontSeeText('Очень быстрый темп');
    }

    public function test_result_page_reports_skipped_answers(): void
    {
        $attempt = $this->makeCompletedAttemptWithTimings([6200, 7100, 8000, 9300, 10400]);

        $unanswered = $attempt->responses()->orderBy('id')->first();
        $unanswered->update(['response' => ['value' => null]]);

        $response = $this->actingAs($attempt->user)->get(route('tests.result', $attempt));

        $response->assertOk();
        $response->assertSeeText('Без ответа: 1');
    }

    public function test_result_page_does_not_count_items_added_after_the_attempt(): void
    {
        $attempt = $this->makeCompletedAttemptWithTimings([6200, 7100, 8000, 9300, 10400]);

        TestItem::query()->create([
            'test_id' => $attempt->test_id,
            'branch' => 'perception',
            'type' => 'single',
            'prompt' => 'Новое задание',
            'config' => ['options' => [['id' => 'o1', 'label' => 'Спокойствие']]],
            'key_data' => ['correct' => 'o1'],
            'sort' => 6,
            'is_active' => true,
        ]);

        $response = $this->actingAs($attempt->user)->get(route('tests.result', $attempt));

        $response->assertOk();
        $response->assertDontSeeText('Без ответа');
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

    /**
     * @param  list<int>  $timings
     */
    private function makeCompletedAttemptWithTimings(array $timings): TestAttempt
    {
        $user = $this->makeUser('ИС-34', 'IS-34');

        $test = Test::query()->create([
            'slug' => 'ei-timed-test',
            'title' => 'Тест ЭИ',
            'kind' => 'ei_express',
            'time_limit_min' => 15,
            'is_active' => true,
            'version' => 1,
        ]);

        $attempt = TestAttempt::query()->create([
            'test_id' => $test->id,
            'user_id' => $user->id,
            'form' => 'A',
            'status' => 'completed',
            'scores' => ['perception' => 70.0],
            'overall' => 70.0,
            'level' => 'above_average',
            'items_total' => count($timings),
            'started_at' => now()->subMinutes(10),
            'finished_at' => now(),
        ]);

        foreach ($timings as $index => $ms) {
            $item = TestItem::query()->create([
                'test_id' => $test->id,
                'branch' => 'perception',
                'type' => 'single',
                'prompt' => 'Задание '.($index + 1),
                'config' => ['options' => [
                    ['id' => 'o1', 'label' => 'Спокойствие'],
                    ['id' => 'o2', 'label' => 'Тревога'],
                ]],
                'key_data' => ['correct' => 'o1'],
                'sort' => $index + 1,
                'is_active' => true,
            ]);

            TestResponse::query()->create([
                'test_attempt_id' => $attempt->id,
                'test_item_id' => $item->id,
                'response' => ['value' => 'o1'],
                'score' => 1.0,
                'max_score' => 1.0,
                'ms' => $ms,
            ]);
        }

        return $attempt;
    }
}
