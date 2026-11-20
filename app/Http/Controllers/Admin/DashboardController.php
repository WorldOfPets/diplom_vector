<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\DiaryEntry;
use App\Models\Module;
use App\Models\TestAttempt;
use App\Models\TrainerAttempt;
use App\Models\TrainerFeedback;
use App\Models\User;
use App\Services\ContentProgressService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $eiAttempts = TestAttempt::query()
            ->where('status', 'completed')
            ->whereHas('test', fn ($query) => $query->whereIn('kind', ['ei_full', 'ei_express', 'ei_situational']));

        $students = User::query()->where('role', 'student')->count();
        $activeStudents = TrainerAttempt::query()
            ->where('status', 'completed')
            ->whereHas('user', fn ($query) => $query->where('role', 'student'))
            ->distinct('user_id')
            ->count('user_id');

        $stats = [
            'users' => User::query()->count(),
            'students' => $students,
            'active' => User::query()->where('last_seen_at', '>=', now()->subDays(7))->count(),
            'trainers' => TrainerAttempt::query()->where('status', 'completed')->count(),
            'tests' => TestAttempt::query()->where('status', 'completed')->count(),
            'diary' => DiaryEntry::query()->count(),
            'avg_ei' => round((float) (clone $eiAttempts)->avg('overall'), 1),
            'certificates' => Certificate::query()->count(),
            'feedback' => TrainerFeedback::query()->count(),
            'feedback_avg' => round((float) TrainerFeedback::query()->avg('rating'), 2),
            'involved' => $students > 0 ? round($activeStudents / $students * 100) : 0,
            'active_students' => $activeStudents,
        ];

        $recentTrainerAttempts = TrainerAttempt::query()
            ->where('status', 'completed')
            ->with(['user', 'trainer.module'])
            ->latest('completed_at')
            ->take(8)
            ->get();

        $recentTestAttempts = TestAttempt::query()
            ->where('status', 'completed')
            ->with(['user', 'test'])
            ->latest('finished_at')
            ->take(6)
            ->get();

        $modules = Module::query()
            ->orderBy('sort')
            ->withCount(['trainers' => fn ($query) => $query->where('is_published', true)])
            ->get()
            ->map(function (Module $module): array {
                $trainerIds = $module->trainers()->pluck('id');
                $completed = TrainerAttempt::query()
                    ->whereIn('trainer_id', $trainerIds)
                    ->where('status', 'completed')
                    ->count();

                return [
                    'module' => $module,
                    'trainers' => $module->trainers_count,
                    'completed' => $completed,
                    'students' => TrainerAttempt::query()
                        ->whereIn('trainer_id', $trainerIds)
                        ->where('status', 'completed')
                        ->distinct('user_id')
                        ->count('user_id'),
                ];
            });

        return view('admin.dashboard', [
            'stats' => $stats,
            'recentTrainerAttempts' => $recentTrainerAttempts,
            'recentTestAttempts' => $recentTestAttempts,
            'modules' => $modules,
            'activity' => $this->activity(),
            'stageStats' => app(ContentProgressService::class)->stageStats(),
            'recentFeedback' => TrainerFeedback::query()
                ->with(['user', 'trainer'])
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }

    /**
     * Завершённые действия по дням за последние 14 дней.
     *
     * @return list<array{date: Carbon, label: string, trainers: int, tests: int, total: int}>
     */
    private function activity(): array
    {
        $from = now()->subDays(13)->startOfDay();

        $trainers = TrainerAttempt::query()
            ->where('status', 'completed')
            ->where('completed_at', '>=', $from)
            ->selectRaw('date(completed_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $tests = TestAttempt::query()
            ->where('status', 'completed')
            ->where('finished_at', '>=', $from)
            ->selectRaw('date(finished_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $days = [];

        for ($i = 0; $i < 14; $i++) {
            $date = $from->copy()->addDays($i);
            $key = $date->toDateString();
            $trainerCount = (int) ($trainers[$key] ?? 0);
            $testCount = (int) ($tests[$key] ?? 0);

            $days[] = [
                'date' => $date,
                'label' => $date->translatedFormat('d.m'),
                'trainers' => $trainerCount,
                'tests' => $testCount,
                'total' => $trainerCount + $testCount,
            ];
        }

        return $days;
    }
}
