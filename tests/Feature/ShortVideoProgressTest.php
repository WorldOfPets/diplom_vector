<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Short;
use App\Models\ShortView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortVideoProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_short_is_counted_only_after_eighty_percent_watched(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $short = Short::query()->create([
            'slug' => 'emotion-signal',
            'title' => 'Эмоция — это сигнал',
            'topic' => 'База',
            'video_path' => 'media/shorts/emotion-signal.mp4',
            'poster_path' => 'media/shorts/emotion-signal.jpg',
            'duration_sec' => 9,
            'sort' => 1,
            'is_published' => true,
        ]);

        $this->actingAs($user)
            ->postJson(route('shorts.view', $short), ['percent' => 79])
            ->assertStatus(422);

        $this->assertDatabaseMissing('short_views', [
            'user_id' => $user->id,
            'short_id' => $short->id,
        ]);

        $this->actingAs($user)
            ->postJson(route('shorts.view', $short), ['percent' => 80])
            ->assertOk()
            ->assertJson(['viewed' => true, 'percent' => 80]);

        $this->assertDatabaseHas('short_views', [
            'user_id' => $user->id,
            'short_id' => $short->id,
            'progress' => 80,
        ]);

        $this->actingAs($user)
            ->postJson(route('shorts.view', $short), ['percent' => 100])
            ->assertOk()
            ->assertJson(['percent' => 100]);

        $this->assertSame(100.0, (float) ShortView::query()->firstOrFail()->progress);
    }
}
