<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\DiaryEntry;
use App\Models\Emotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiaryTextQualityTest extends TestCase
{
    use RefreshDatabase;

    public function test_diary_rejects_keyboard_mash(): void
    {
        [$user, $emotion] = $this->makeUserAndEmotion();

        $this->actingAs($user)
            ->post(route('diary.store'), [
                'slot' => 'now',
                'emotion_id' => $emotion->id,
                'intensity' => 3,
                'cause' => 'встреча с мастером',
                'note' => 'фывап билеберда',
            ])
            ->assertSessionHasErrors('note');

        $this->assertDatabaseCount('diary_entries', 0);
    }

    public function test_diary_rejects_keyboard_mash_in_cause(): void
    {
        [$user, $emotion] = $this->makeUserAndEmotion();

        $this->actingAs($user)
            ->post(route('diary.store'), [
                'slot' => 'now',
                'emotion_id' => $emotion->id,
                'intensity' => 3,
                'cause' => 'йцукен',
                'note' => 'Стало легче после паузы',
            ])
            ->assertSessionHasErrors('cause');

        $this->assertDatabaseCount('diary_entries', 0);
    }

    public function test_diary_saves_normal_text(): void
    {
        [$user, $emotion] = $this->makeUserAndEmotion();

        $this->actingAs($user)
            ->post(route('diary.store'), [
                'slot' => 'now',
                'emotion_id' => $emotion->id,
                'intensity' => 4,
                'cause' => 'Разговор с мастером',
                'note' => 'Стало легче после паузы',
            ])
            ->assertRedirect(route('diary.index'));

        $this->assertDatabaseCount('diary_entries', 1);
        $this->assertSame('Стало легче после паузы', DiaryEntry::query()->firstOrFail()->note);
    }

    /**
     * @return array{0: User, 1: Emotion}
     */
    private function makeUserAndEmotion(): array
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $emotion = Emotion::query()->create([
            'slug' => 'calm',
            'name' => 'Спокойствие',
            'group' => 'primary',
            'emoji' => '😌',
            'sort' => 1,
        ]);

        return [$user, $emotion];
    }
}
