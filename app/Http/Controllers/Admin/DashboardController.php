<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiaryEntry;
use App\Models\Module;
use App\Models\TestAttempt;
use App\Models\TrainerAttempt;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $eiAttempts = TestAttempt::query()
            ->where('status', 'completed')
            ->whereHas('test', fn ($query) => $query->whereIn('kind', ['ei_full', 'ei_express', 'ei_situational']));

        $stats = [
            'users' => User::query()->count(),
            'students' => User::query()->where('role', 'student')->count(),
            'active' => User::query()->where('last_seen_at', '>=', now()->subDays(7))->count(),
            'trainers' => TrainerAttempt::query()->where('status', 'completed')->count(),
            'tests' => TestAttempt::query()->where('status', 'completed')->count(),
            'diary' => DiaryEntry::query()->count(),
            'avg_ei' => round((float) (clone $eiAttempts)->avg('overall'), 1),
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
        ]);
    }
}
