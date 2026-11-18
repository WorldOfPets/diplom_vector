<?php

namespace App\Http\Controllers;

use App\Models\DiaryEntry;
use App\Models\Emotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiaryController extends Controller
{
    private const SLOTS = [
        'morning' => 'Утро',
        'day' => 'День',
        'evening' => 'Вечер',
        'now' => 'Сейчас',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();

        $entries = DiaryEntry::query()
            ->where('user_id', $user->id)
            ->where('entry_date', '>=', today()->subDays(20))
            ->with('emotion')
            ->orderBy('entry_date')
            ->get();

        $todayEntries = $entries
            ->filter(fn (DiaryEntry $entry): bool => $entry->entry_date->isToday())
            ->keyBy('slot');

        $dates = collect(range(13, 0))
            ->map(fn (int $days): string => today()->subDays($days)->toDateString());

        $byDate = $entries->groupBy(fn (DiaryEntry $entry): string => $entry->entry_date->toDateString());

        $chart = [
            'labels' => $dates->map(fn (string $date): string => date('d.m', strtotime($date)))->values(),
            'intensity' => $dates->map(function (string $date) use ($byDate): ?float {
                $dayEntries = $byDate->get($date);

                return $dayEntries?->avg('intensity');
            })->values(),
        ];

        $emotionCounts = $entries
            ->filter(fn (DiaryEntry $entry): bool => $entry->emotion !== null)
            ->groupBy('emotion_id')
            ->map(fn ($group): array => [
                'label' => $group->first()->emotion->name,
                'color' => $group->first()->emotion->color ?? '#2D5BFF',
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->take(6)
            ->values();

        return view('diary.index', [
            'emotions' => Emotion::query()->orderBy('sort')->get(),
            'todayEntries' => $todayEntries,
            'entries' => $entries->sortByDesc('entry_date')->take(12),
            'slots' => self::SLOTS,
            'chart' => $chart,
            'emotionCounts' => $emotionCounts,
            'entriesCount' => DiaryEntry::query()->where('user_id', $user->id)->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'entry_date' => ['nullable', 'date', 'before_or_equal:today'],
            'slot' => ['required', 'in:morning,day,evening,now'],
            'emotion_id' => ['required', 'integer', 'exists:emotions,id'],
            'intensity' => ['required', 'integer', 'between:1,5'],
            'cause' => ['nullable', 'string', 'max:120'],
            'body_zones' => ['nullable', 'array', 'max:12'],
            'body_zones.*' => ['string', 'max:40'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        DiaryEntry::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'entry_date' => $data['entry_date'] ?? today()->toDateString(),
                'slot' => $data['slot'],
            ],
            [
                'emotion_id' => $data['emotion_id'],
                'intensity' => $data['intensity'],
                'cause' => $data['cause'] ?? null,
                'body_zones' => $data['body_zones'] ?? null,
                'note' => $data['note'] ?? null,
            ],
        );

        return redirect()
            ->route('diary.index')
            ->with('status', 'Запись сохранена.');
    }

    public function destroy(Request $request, DiaryEntry $entry): RedirectResponse
    {
        abort_unless($entry->user_id === $request->user()->id, 403);

        $entry->delete();

        return redirect()->route('diary.index')->with('status', 'Запись удалена.');
    }
}
