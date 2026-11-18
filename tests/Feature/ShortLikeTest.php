<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Group;
use App\Models\Short;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_like_can_be_toggled_without_comments(): void
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
            ->get(route('shorts.index'))
            ->assertOk()
            ->assertSee('Эмоция — это сигнал')
            ->assertDontSee('Комментарии');

        $this->actingAs($user)
            ->postJson(route('shorts.like', $short))
            ->assertOk()
            ->assertJson(['liked' => true, 'likes' => 1]);

        $this->actingAs($user)
            ->postJson(route('shorts.like', $short))
            ->assertOk()
            ->assertJson(['liked' => false, 'likes' => 0]);
    }
}
