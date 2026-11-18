<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\DiaryEntry;
use App\Models\User;
use App\Services\ContentProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiaryProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_diary_progress_counts_distinct_practice_days_not_entries(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        foreach (range(0, 3) as $index) {
            $entry = DiaryEntry::query()->create([
                'user_id' => $user->id,
                'entry_date' => today()->subDays(10 + $index)->toDateString(),
                'slot' => 'now',
                'intensity' => 3,
            ]);

            $entry->forceFill([
                'created_at' => now(),
                'updated_at' => now(),
            ])->saveQuietly();
        }

        $service = app(ContentProgressService::class);

        $diary = collect($service->summary($user)['categories'])->firstWhere('key', 'diary');

        $this->assertSame(1, $diary['done']);
        $this->assertSame(7, $diary['total']);

        foreach (range(1, 6) as $offset) {
            $entry = DiaryEntry::query()->create([
                'user_id' => $user->id,
                'entry_date' => today()->subDays($offset)->toDateString(),
                'slot' => 'now',
                'intensity' => 3,
            ]);

            $entry->forceFill([
                'created_at' => now()->subDays($offset),
                'updated_at' => now()->subDays($offset),
            ])->saveQuietly();
        }

        $diary = collect($service->summary($user)['categories'])->firstWhere('key', 'diary');

        $this->assertSame(7, $diary['done']);
        $this->assertSame(7, $diary['total']);
    }
}
