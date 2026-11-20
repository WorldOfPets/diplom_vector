<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\Module;
use App\Models\Trainer;
use App\Models\TrainerAttempt;
use App\Models\TrainerFeedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_cannot_open_admin_reports_or_exports(): void
    {
        $student = User::factory()->create([
            'role' => UserRole::Student,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $this->actingAs($student)->get(route('admin.feedback.index'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.export.progress'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.export.feedback'))->assertForbidden();
    }

    public function test_feedback_report_shows_ratings_and_comments(): void
    {
        [$admin, $student, $trainer] = $this->fixtures();

        $attempt = TrainerAttempt::query()->create([
            'user_id' => $student->id,
            'trainer_id' => $trainer->id,
            'trainer_version' => 1,
            'status' => 'completed',
            'percent' => 80,
            'score' => 80,
            'max_score' => 100,
            'passed' => true,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        TrainerFeedback::query()->create([
            'user_id' => $student->id,
            'trainer_id' => $trainer->id,
            'attempt_id' => $attempt->id,
            'rating' => 2,
            'comment' => 'Слишком быстро меняются карточки',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.feedback.index'))
            ->assertOk()
            ->assertSee('Качество тренажёров')
            ->assertSee('Слишком быстро меняются карточки')
            ->assertSee('2,00');
    }

    public function test_progress_and_feedback_exports_return_csv_with_bom(): void
    {
        [$admin, $student, $trainer] = $this->fixtures();

        $attempt = TrainerAttempt::query()->create([
            'user_id' => $student->id,
            'trainer_id' => $trainer->id,
            'trainer_version' => 1,
            'status' => 'completed',
            'percent' => 95,
            'score' => 95,
            'max_score' => 100,
            'passed' => true,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        TrainerFeedback::query()->create([
            'user_id' => $student->id,
            'trainer_id' => $trainer->id,
            'attempt_id' => $attempt->id,
            'rating' => 5,
        ]);

        $progress = $this->actingAs($admin)->get(route('admin.export.progress'));
        $progress->assertOk();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $progress->streamedContent());
        $this->assertStringContainsString('certificate_ready', $progress->streamedContent());

        $feedback = $this->actingAs($admin)->get(route('admin.export.feedback'));
        $feedback->assertOk();
        $this->assertStringContainsString('rating;comment', $feedback->streamedContent());
        $this->assertStringContainsString($student->name, $feedback->streamedContent());
    }

    public function test_csv_export_escapes_formula_prefixes(): void
    {
        [$admin, $student] = $this->fixtures();
        $student->update(['name' => '=HYPERLINK("http://evil.test")']);

        $response = $this->actingAs($admin)->get(route('admin.export.users'));

        $response->assertOk();
        $this->assertStringContainsString("'=HYPERLINK", $response->streamedContent());
    }

    /**
     * @return array{0: User, 1: User, 2: Trainer}
     */
    private function fixtures(): array
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $group = Group::query()->create(['name' => 'ИС-21', 'code' => '09.02.07', 'course' => 2]);

        $student = User::factory()->create([
            'role' => UserRole::Student,
            'group_id' => $group->id,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $trainer = Trainer::query()->create([
            'module_id' => Module::query()->create([
                'slug' => 'test-module',
                'title' => 'Тестовый модуль',
                'subtitle' => 'Тестовый модуль',
                'sort' => 1,
                'is_published' => true,
            ])->id,
            'slug' => 'test-trainer',
            'title' => 'Тестовый тренажёр',
            'subtitle' => 'Тестовый тренажёр',
            'engine' => 'reflection',
            'config' => ['prompt' => 'Выберите карту'],
            'settings' => ['threshold' => 0],
            'points' => 10,
            'duration_min' => 5,
            'sort' => 1,
            'is_published' => true,
        ]);

        return [$admin, $student, $trainer];
    }
}
