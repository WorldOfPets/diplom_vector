<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\TheoryArticle;
use App\Models\TheoryProgress;
use App\Models\User;
use App\Services\ContentProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TheoryStepsTest extends TestCase
{
    use RefreshDatabase;

    public function test_theory_has_three_steps_and_the_task_requires_exact_answers(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $article = TheoryArticle::query()->create([
            'slug' => 'test-theory',
            'title' => 'Тестовая теория',
            'body' => 'Текст статьи',
            'source_url' => 'https://example.com/source',
            'task' => [
                'text' => 'Эмоция — это {{1}}, а не {{2}}.',
                'answers' => ['1' => 'сигнал', '2' => 'приговор'],
                'options' => ['сигнал', 'приговор', 'оценка', 'факт'],
            ],
            'reading_minutes' => 5,
            'sort' => 1,
            'is_published' => true,
        ]);

        $this->actingAs($user)
            ->postJson(route('theory.task', $article), [
                'answers' => ['1' => 'сигнал', '2' => 'оценка'],
            ])
            ->assertStatus(422);

        $this->assertNull(TheoryProgress::query()->where('user_id', $user->id)->first()?->task_completed_at);

        $this->actingAs($user)
            ->get(route('theory.source', $article))
            ->assertRedirect('https://example.com/source');

        $this->actingAs($user)
            ->postJson(route('theory.read', $article))
            ->assertOk();

        $this->actingAs($user)
            ->postJson(route('theory.task', $article), [
                'answers' => ['1' => 'сигнал', '2' => 'приговор'],
            ])
            ->assertOk()
            ->assertJson(['completed' => true]);

        $progress = TheoryProgress::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertNotNull($progress->read_at);
        $this->assertNotNull($progress->source_opened_at);
        $this->assertNotNull($progress->task_completed_at);

        $theory = collect(app(ContentProgressService::class)->summary($user)['categories'])->firstWhere('key', 'theory');

        $this->assertSame(3, $theory['done']);
        $this->assertSame(3, $theory['total']);
    }
}
