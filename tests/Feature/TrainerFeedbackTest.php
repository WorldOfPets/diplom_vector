<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Module;
use App\Models\Trainer;
use App\Models\TrainerAttempt;
use App\Models\TrainerFeedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainerFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_high_rating_is_saved_without_comment(): void
    {
        [$user, $attempt] = $this->makeAttempt();

        $this->actingAs($user)
            ->post(route('trainer-feedback.store', $attempt), ['rating' => 5])
            ->assertRedirect(route('trainers.result', $attempt).'#feedback');

        $feedback = TrainerFeedback::query()->firstOrFail();

        $this->assertSame(5, $feedback->rating);
        $this->assertNull($feedback->comment);
        $this->assertSame($attempt->id, $feedback->attempt_id);
    }

    public function test_low_rating_requires_a_comment(): void
    {
        [$user, $attempt] = $this->makeAttempt();

        $this->actingAs($user)
            ->post(route('trainer-feedback.store', $attempt), ['rating' => 2])
            ->assertSessionHasErrors('comment');

        $this->assertDatabaseCount('trainer_feedback', 0);
    }

    public function test_low_rating_with_comment_is_saved(): void
    {
        [$user, $attempt] = $this->makeAttempt();

        $this->actingAs($user)
            ->post(route('trainer-feedback.store', $attempt), [
                'rating' => 1,
                'comment' => 'Непонятно, что делать на втором шаге',
            ])
            ->assertRedirect(route('trainers.result', $attempt).'#feedback');

        $feedback = TrainerFeedback::query()->firstOrFail();

        $this->assertSame(1, $feedback->rating);
        $this->assertSame('Непонятно, что делать на втором шаге', $feedback->comment);
    }

    public function test_foreign_attempt_cannot_be_rated(): void
    {
        [, $attempt] = $this->makeAttempt();

        $other = User::factory()->create([
            'role' => UserRole::Student,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $this->actingAs($other)
            ->post(route('trainer-feedback.store', $attempt), ['rating' => 5])
            ->assertForbidden();
    }

    public function test_result_page_shows_the_feedback_form(): void
    {
        [$user, $attempt] = $this->makeAttempt();

        $this->actingAs($user)
            ->get(route('trainers.result', $attempt))
            ->assertOk()
            ->assertSee('Оцените упражнение')
            ->assertSee('Сначала оцените упражнение');
    }

    /**
     * @return array{0: User, 1: TrainerAttempt}
     */
    private function makeAttempt(): array
    {
        $module = Module::query()->create([
            'slug' => 'understand-self-feedback',
            'title' => 'Понимаю себя',
            'subtitle' => 'Тестовый модуль',
            'sort' => 1,
            'is_published' => true,
        ]);

        $trainer = Trainer::query()->create([
            'module_id' => $module->id,
            'slug' => 'card-of-day-feedback',
            'title' => 'Карта дня',
            'subtitle' => 'Тестовый тренажёр',
            'engine' => 'reflection',
            'config' => ['prompt' => 'Выберите карту'],
            'settings' => ['threshold' => 0],
            'points' => 10,
            'duration_min' => 5,
            'sort' => 1,
            'is_published' => true,
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Student,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $attempt = TrainerAttempt::query()->create([
            'user_id' => $user->id,
            'trainer_id' => $trainer->id,
            'trainer_version' => 1,
            'status' => 'completed',
            'response' => [],
            'config_snapshot' => $trainer->config,
            'metrics' => [],
            'score' => 100,
            'max_score' => 100,
            'percent' => 100,
            'passed' => true,
            'feedback' => null,
            'duration_sec' => 60,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);

        return [$user, $attempt];
    }
}
