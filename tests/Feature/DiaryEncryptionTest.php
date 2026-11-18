<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\DiaryEntry;
use App\Models\Emotion;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DiaryEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_diary_text_is_encrypted_and_available_only_to_its_owner(): void
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

        $other = User::factory()->create([
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

        $this->actingAs($user)
            ->post(route('diary.store'), [
                'slot' => 'now',
                'emotion_id' => $emotion->id,
                'intensity' => 4,
                'cause' => 'Разговор с мастером',
                'body_zones' => ['голова', 'грудь'],
                'note' => 'Стало легче после паузы',
            ])
            ->assertRedirect(route('diary.index'));

        $raw = DB::table('diary_entries')->first();

        $this->assertNotNull($raw);
        $this->assertNotSame('Разговор с мастером', $raw->cause);
        $this->assertNotSame('Стало легче после паузы', $raw->note);
        $this->assertStringNotContainsString('Разговор с мастером', (string) $raw->cause);
        $this->assertStringNotContainsString('Стало легче после паузы', (string) $raw->note);
        $this->assertStringNotContainsString('голова', (string) $raw->body_zones);

        $entry = DiaryEntry::query()->firstOrFail();

        $this->assertSame('Разговор с мастером', $entry->cause);
        $this->assertSame('Стало легче после паузы', $entry->note);
        $this->assertSame(['голова', 'грудь'], $entry->body_zones);

        $this->actingAs($other)
            ->get(route('diary.index'))
            ->assertOk()
            ->assertDontSee('Разговор с мастером')
            ->assertDontSee('Стало легче после паузы');

        $this->actingAs($other)
            ->delete(route('diary.destroy', $entry))
            ->assertForbidden();

        $this->assertDatabaseHas('diary_entries', ['id' => $entry->id]);
    }

    public function test_diary_entry_can_be_saved_without_optional_text(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $emotion = Emotion::query()->create([
            'slug' => 'joy',
            'name' => 'Радость',
            'group' => 'primary',
            'emoji' => '😊',
            'sort' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('diary.store'), [
                'slot' => 'morning',
                'emotion_id' => $emotion->id,
                'intensity' => 3,
            ])
            ->assertRedirect(route('diary.index'));

        $entry = DiaryEntry::query()->firstOrFail();

        $this->assertNull($entry->cause);
        $this->assertNull($entry->note);
        $this->assertNull($entry->body_zones);
    }
}
