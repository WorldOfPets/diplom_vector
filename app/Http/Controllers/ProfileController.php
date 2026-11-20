<?php

namespace App\Http\Controllers;

use App\Enums\EiBranch;
use App\Models\DiaryEntry;
use App\Models\Group;
use App\Models\TestAttempt;
use App\Models\TrainerAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $eiAttempts = TestAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereHas('test', fn ($query) => $query->whereIn('kind', ['ei_full', 'ei_express', 'ei_situational']))
            ->with('test')
            ->orderBy('finished_at')
            ->get();

        $motivationAttempts = TestAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereHas('test', fn ($query) => $query->where('kind', 'motivation'))
            ->with('test')
            ->latest('finished_at')
            ->take(4)
            ->get();

        $latestEi = $eiAttempts->last();

        $branches = collect(EiBranch::eiBranches())
            ->map(fn (EiBranch $branch): array => [
                'label' => $branch->shortLabel(),
                'value' => $latestEi?->branchScore($branch->value),
                'color' => $branch->color(),
            ]);

        $attemptTrend = $eiAttempts->map(fn (TestAttempt $attempt): array => [
            'label' => $attempt->finished_at?->format('d.m H:i') ?? '—',
            'value' => (float) $attempt->overall,
        ]);

        $trainerAttempts = TrainerAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->with('trainer.module')
            ->latest('completed_at')
            ->get();

        $diaryEntries = DiaryEntry::query()
            ->where('user_id', $user->id)
            ->with('emotion')
            ->latest('entry_date')
            ->take(30)
            ->get();

        $emotionCounts = $diaryEntries
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

        return view('profile.index', [
            'user' => $user,
            'eiAttempts' => $eiAttempts->reverse(),
            'motivationAttempts' => $motivationAttempts,
            'latestEi' => $latestEi,
            'branches' => $branches,
            'attemptTrend' => $attemptTrend->values(),
            'trainerAttempts' => $trainerAttempts,
            'trainerStats' => [
                'completed' => $trainerAttempts->unique('trainer_id')->count(),
                'average' => $trainerAttempts->isEmpty() ? 0 : round($trainerAttempts->avg('percent')),
                'passed' => $trainerAttempts->where('passed', true)->unique('trainer_id')->count(),
            ],
            'diaryStats' => [
                'entries' => DiaryEntry::query()->where('user_id', $user->id)->count(),
                'days' => DiaryEntry::query()->where('user_id', $user->id)->distinct()->count('entry_date'),
                'top_emotion' => $emotionCounts->first()['label'] ?? null,
            ],
            'emotionCounts' => $emotionCounts,
            'groups' => Group::query()->orderBy('course')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'course' => ['nullable', 'integer', 'between:1,4'],
            'specialty' => ['nullable', 'string', 'max:190'],
        ], [
            'name.required' => 'Укажите, как к вам обращаться.',
        ]);

        $request->user()->fill([
            'name' => $data['name'],
            'group_id' => $data['group_id'] ?? null,
            'course' => $data['course'] ?? null,
            'specialty' => $data['specialty'] ?? null,
        ])->save();

        return redirect()
            ->route('profile.index')
            ->with('status', 'Личные данные обновлены.');
    }
}
