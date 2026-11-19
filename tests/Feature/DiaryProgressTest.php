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

    public function test_diary_progress_counts_morning_day_and_evening_and_caps_at_twenty_one(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        $createEntry = function (int $daysAgo, string $slot) use ($user): void {
            $entry = DiaryEntry::query()->create([
                'user_id' => $user->id,
                'entry_date' => today()->subDays($daysAgo)->toDateString(),
                'slot' => $slot,
                'intensity' => 3,
            ]);

            $entry->forceFill([
                'created_at' => now()->subDays($daysAgo),
                'updated_at' => now()->subDays($daysAgo),
            ])->saveQuietly();
        };

        foreach (['morning', 'day', 'evening', 'now'] as $slot) {
            $createEntry(0, $slot);
        }

        $service = app(ContentProgressService::class);
        $diary = collect($service->summary($user)['categories'])->firstWhere('key', 'diary');

        $this->assertSame(3, $diary['done']);
        $this->assertSame(21, $diary['total']);

        foreach (range(1, 6) as $daysAgo) {
            foreach (['morning', 'day', 'evening'] as $slot) {
                $createEntry($daysAgo, $slot);
            }
        }

        $diary = collect($service->summary($user)['categories'])->firstWhere('key', 'diary');

        $this->assertSame(21, $diary['done']);
        $this->assertSame(21, $diary['total']);

        $createEntry(7, 'morning');

        $diary = collect($service->summary($user)['categories'])->firstWhere('key', 'diary');

        $this->assertSame(21, $diary['done']);
        $this->assertSame(21, $diary['total']);
    }

    public function test_diary_progress_counts_only_the_first_seven_days(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'consent_at' => now(),
            'consent_version' => '1.0',
        ]);

        foreach (range(0, 7) as $daysAgo) {
            $entry = DiaryEntry::query()->create([
                'user_id' => $user->id,
                'entry_date' => today()->subDays($daysAgo)->toDateString(),
                'slot' => 'morning',
                'intensity' => 3,
            ]);

            $entry->forceFill([
                'created_at' => now()->subDays($daysAgo),
                'updated_at' => now()->subDays($daysAgo),
            ])->saveQuietly();
        }

        $diary = collect(app(ContentProgressService::class)->summary($user)['categories'])->firstWhere('key', 'diary');

        $this->assertSame(7, $diary['done']);
        $this->assertSame(21, $diary['total']);
    }
}
