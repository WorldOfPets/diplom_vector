<?php

namespace App\Http\Controllers;

use App\Models\DiaryEntry;
use App\Models\Module;
use App\Models\Short;
use App\Models\Test;
use App\Models\Trainer;
use App\Models\TrainerAttempt;
use App\Services\ContentProgressService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $modules = Module::query()
            ->published()
            ->with(['trainers' => fn ($query) => $query->where('is_published', true)])
            ->orderBy('sort')
            ->get();

        $completedTrainerIds = TrainerAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->pluck('trainer_id')
            ->unique();

        $modules->each(function (Module $module) use ($completedTrainerIds): void {
            $total = $module->trainers->count();
            $done = $module->trainers->whereIn('id', $completedTrainerIds)->count();
            $module->setAttribute('progress', $total > 0 ? (int) round($done / $total * 100) : 0);
            $module->setAttribute('completed_count', $done);
        });

        $nextTrainer = Trainer::query()
            ->where('is_published', true)
            ->whereHas('module', fn ($query) => $query->where('is_published', true))
            ->whereNotIn('id', $completedTrainerIds)
            ->with('module')
            ->orderBy('module_id')
            ->orderBy('sort')
            ->first();

        $tests = Test::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->each(function (Test $test) use ($user): void {
                $test->setAttribute('latest_attempt', $test->attempts()
                    ->where('user_id', $user->id)
                    ->where('status', 'completed')
                    ->latest('finished_at')
                    ->first());
            });

        $todayEntries = DiaryEntry::query()
            ->where('user_id', $user->id)
            ->whereDate('entry_date', today())
            ->with('emotion')
            ->get();

        $recentAttempts = TrainerAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->with('trainer.module')
            ->latest('completed_at')
            ->take(4)
            ->get();

        $featuredShort = Short::query()
            ->published()
            ->orderBy('sort')
            ->first();

        $contentProgress = app(ContentProgressService::class)->sync($user);

        return view('dashboard', [
            'modules' => $modules,
            'nextTrainer' => $nextTrainer,
            'tests' => $tests,
            'todayEntries' => $todayEntries,
            'recentAttempts' => $recentAttempts,
            'featuredShort' => $featuredShort,
            'contentProgress' => $contentProgress,
            'streak' => $this->diaryStreak($user->id),
            'completedCount' => $completedTrainerIds->count(),
            'totalTrainers' => $modules->sum(fn (Module $module): int => $module->trainers->count()),
        ]);
    }

    private function diaryStreak(int $userId): int
    {
        $dates = DiaryEntry::query()
            ->where('user_id', $userId)
            ->distinct()
            ->orderByDesc('entry_date')
            ->pluck('entry_date')
            ->map(fn ($date): string => $date->toDateString());

        if ($dates->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $cursor = today();

        if ($dates->first() !== $cursor->toDateString()) {
            $cursor = $cursor->subDay();
        }

        foreach ($dates as $date) {
            if ($date !== $cursor->toDateString()) {
                break;
            }

            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
    }
}
