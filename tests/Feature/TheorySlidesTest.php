<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\TheoryArticle;
use App\Models\TheoryProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TheorySlidesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_slide_needs_fifteen_seconds_before_the_step_is_counted(): void
    {
        [$user, $article] = $this->makeArticle();

        $this->actingAs($user)
            ->postJson(route('theory.slides', $article), ['index' => 0, 'seconds' => 15])
            ->assertOk()
            ->assertJson(['completed' => false, 'viewed' => 1, 'total' => 2]);

        $progress = TheoryProgress::query()->firstOrFail();
        $this->assertNull($progress->slides_completed_at);
        $this->assertFalse($article->firstStepCompleted($progress));

        $this->actingAs($user)
            ->postJson(route('theory.slides', $article), ['index' => 1, 'seconds' => 15])
            ->assertOk()
            ->assertJson(['completed' => true, 'viewed' => 2]);

        $progress->refresh();
        $this->assertNotNull($progress->slides_completed_at);
        $this->assertTrue($article->firstStepCompleted($progress));
        $this->assertSame(1, $article->completedSteps($progress));
    }

    public function test_short_view_does_not_count(): void
    {
        [$user, $article] = $this->makeArticle();

        $this->actingAs($user)
            ->postJson(route('theory.slides', $article), ['index' => 0, 'seconds' => 4])
            ->assertOk()
            ->assertJson(['viewed' => 0, 'completed' => false]);

        $this->assertNull(TheoryProgress::query()->firstOrFail()->slides_completed_at);
    }

    public function test_index_outside_the_deck_is_rejected(): void
    {
        [$user, $article] = $this->makeArticle();

        $this->actingAs($user)
            ->postJson(route('theory.slides', $article), ['index' => 5, 'seconds' => 15])
            ->assertStatus(422);
    }

    public function test_article_without_slides_returns_not_found(): void
    {
        [$user, $article] = $this->makeArticle(slides: null);

        $this->actingAs($user)
            ->postJson(route('theory.slides', $article), ['index' => 0, 'seconds' => 15])
            ->assertNotFound();
    }

    /**
     * @return array{0: User, 1: TheoryArticle}
     */
    private function makeArticle(?array $slides = ['media/slides/deck-04/slide-01.jpg', 'media/slides/deck-04/slide-02.jpg']): array
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $article = TheoryArticle::query()->create([
            'slug' => 'what-is-ei-slides',
            'title' => 'Р§С‚Рѕ С‚Р°РєРѕРµ Р­Р',
            'excerpt' => 'РўРµСЃС‚',
            'body' => 'РўРµСЃС‚',
            'slides' => $slides,
            'reading_minutes' => 5,
            'sort' => 1,
            'is_published' => true,
        ]);

        return [$user, $article];
    }
}
