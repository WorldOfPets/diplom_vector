<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\DiaryEntry;
use App\Models\Emotion;
use App\Models\Feedback;
use App\Models\ProgressSnapshot;
use App\Models\Short;
use App\Models\ShortView;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\TheoryArticle;
use App\Models\TheoryProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_last_admin_cannot_be_demoted(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'role' => UserRole::Student->value,
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors('role');

        $this->assertSame(UserRole::Admin, $admin->refresh()->role);
    }

    public function test_admin_can_delete_a_student_with_their_diary(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);
        $student = User::factory()->create([
            'role' => UserRole::Student,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);
        $emotion = Emotion::query()->create([
            'slug' => 'joy',
            'name' => 'Радость',
            'group' => 'primary',
            'sort' => 1,
        ]);
        $diary = DiaryEntry::query()->create([
            'user_id' => $student->id,
            'entry_date' => today(),
            'slot' => 'now',
            'emotion_id' => $emotion->id,
            'intensity' => 4,
            'note' => 'Удаляемая запись',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $student))
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $student->id]);
        $this->assertDatabaseMissing('diary_entries', ['id' => $diary->id]);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_reset_clears_progress_but_keeps_diary(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);
        $student = User::factory()->create([
            'role' => UserRole::Student,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $test = Test::query()->create([
            'slug' => 'express',
            'title' => 'Экспресс',
            'kind' => 'ei_express',
            'time_limit_min' => 15,
            'is_active' => true,
            'config' => [],
            'version' => 1,
        ]);
        $attempt = TestAttempt::query()->create([
            'test_id' => $test->id,
            'user_id' => $student->id,
            'status' => 'completed',
            'overall' => 80,
            'finished_at' => now(),
        ]);
        $short = Short::query()->create([
            'slug' => 'signal',
            'title' => 'Сигнал',
            'topic' => 'База',
            'video_path' => 'media/shorts/emotion-signal.mp4',
            'duration_sec' => 9,
            'sort' => 1,
            'is_published' => true,
        ]);
        $article = TheoryArticle::query()->create([
            'slug' => 'article',
            'title' => 'Статья',
            'body' => 'Текст',
            'sort' => 1,
            'is_published' => true,
        ]);
        $emotion = Emotion::query()->create([
            'slug' => 'calm',
            'name' => 'Спокойствие',
            'group' => 'primary',
            'sort' => 1,
        ]);

        ShortView::query()->create([
            'user_id' => $student->id,
            'short_id' => $short->id,
            'viewed_at' => now(),
            'progress' => 100,
        ]);
        TheoryProgress::query()->create([
            'user_id' => $student->id,
            'theory_article_id' => $article->id,
            'read_at' => now(),
        ]);
        ProgressSnapshot::query()->create([
            'user_id' => $student->id,
            'stage' => 'initial',
            'percent' => 10,
            'counts' => [],
            'captured_at' => now(),
        ]);
        Feedback::query()->create([
            'user_id' => $student->id,
            'rating' => 5,
            'liked' => 'ok',
        ]);
        Certificate::query()->create([
            'user_id' => $student->id,
            'number' => 'VK-TEST-1',
            'percent' => 100,
            'issued_at' => now(),
        ]);
        $diary = DiaryEntry::query()->create([
            'user_id' => $student->id,
            'entry_date' => today(),
            'slot' => 'now',
            'emotion_id' => $emotion->id,
            'intensity' => 3,
            'note' => 'Личная запись',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.reset', $student))
            ->assertRedirect();

        $this->assertDatabaseMissing('test_attempts', ['id' => $attempt->id]);
        $this->assertDatabaseMissing('short_views', ['user_id' => $student->id]);
        $this->assertDatabaseMissing('theory_progress', ['user_id' => $student->id]);
        $this->assertDatabaseMissing('progress_snapshots', ['user_id' => $student->id]);
        $this->assertDatabaseMissing('feedback', ['user_id' => $student->id]);
        $this->assertDatabaseMissing('certificates', ['user_id' => $student->id]);
        $this->assertDatabaseHas('diary_entries', ['id' => $diary->id]);
    }
}
