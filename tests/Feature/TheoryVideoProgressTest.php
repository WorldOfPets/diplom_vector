<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\TheoryArticle;
use App\Models\TheoryProgress;
use App\Models\User;
use App\Services\ContentProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TheoryVideoProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_theory_video_counts_only_after_eighty_percent_watched(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $article = TheoryArticle::query()->create([
            'slug' => 'video-lesson',
            'title' => 'Видеоурок',
            'body' => 'Текст',
            'video_url' => 'https://rutube.ru/video/abcdef123456/',
            'reading_minutes' => 5,
            'sort' => 1,
            'is_published' => true,
        ]);

        $this->actingAs($user)
            ->postJson(route('theory.read', $article))
            ->assertOk();

        $summary = app(ContentProgressService::class)->summary($user);
        $theory = collect($summary['categories'])->firstWhere('key', 'theory');

        $this->assertSame(0, $theory['done']);

        $this->actingAs($user)
            ->postJson(route('theory.video', $article), ['percent' => 79])
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson(route('theory.video', $article), ['percent' => 80])
            ->assertOk();

        $summary = app(ContentProgressService::class)->summary($user);
        $theory = collect($summary['categories'])->firstWhere('key', 'theory');

        $this->assertSame(1, $theory['done']);

        $progress = TheoryProgress::query()->firstOrFail();

        $this->assertNotNull($progress->read_at);
        $this->assertNotNull($progress->video_completed_at);
        $this->assertSame(80.0, (float) $progress->video_progress);
    }

    public function test_local_theory_video_counts_only_after_eighty_percent_watched(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $article = TheoryArticle::query()->create([
            'slug' => 'local-video-lesson',
            'title' => 'Локальный видеоурок',
            'body' => 'Текст',
            'video_path' => 'media/theory/emotional-intelligence.mp4',
            'reading_minutes' => 5,
            'sort' => 1,
            'is_published' => true,
        ]);

        $this->assertTrue($article->hasTrackableVideo());

        $this->actingAs($user)
            ->get(route('theory.show', $article))
            ->assertOk()
            ->assertSee('theoryLocalVideo', false)
            ->assertSee('<video', false);

        $this->actingAs($user)
            ->postJson(route('theory.read', $article))
            ->assertOk();

        $summary = app(ContentProgressService::class)->summary($user);
        $theory = collect($summary['categories'])->firstWhere('key', 'theory');
        $this->assertSame(0, $theory['done']);

        $this->actingAs($user)
            ->postJson(route('theory.video', $article), ['percent' => 79])
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson(route('theory.video', $article), ['percent' => 80])
            ->assertOk();

        $summary = app(ContentProgressService::class)->summary($user);
        $theory = collect($summary['categories'])->firstWhere('key', 'theory');
        $this->assertSame(1, $theory['done']);

        $progress = TheoryProgress::query()->firstOrFail();
        $this->assertNotNull($progress->video_completed_at);
        $this->assertSame(80.0, (float) $progress->video_progress);
    }

    public function test_article_without_trackable_video_counts_after_reading(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $article = TheoryArticle::query()->create([
            'slug' => 'plain-article',
            'title' => 'Статья',
            'body' => 'Текст',
            'reading_minutes' => 5,
            'sort' => 1,
            'is_published' => true,
        ]);

        $this->actingAs($user)
            ->postJson(route('theory.read', $article))
            ->assertOk();

        $summary = app(ContentProgressService::class)->summary($user);
        $theory = collect($summary['categories'])->firstWhere('key', 'theory');

        $this->assertSame(1, $theory['done']);
    }
}
