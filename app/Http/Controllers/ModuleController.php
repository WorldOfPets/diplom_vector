<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\TrainerAttempt;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $completedIds = TrainerAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->pluck('trainer_id')
            ->unique();

        $modules = Module::query()
            ->published()
            ->with(['trainers' => fn ($query) => $query->where('is_published', true)])
            ->orderBy('sort')
            ->get()
            ->each(function (Module $module) use ($completedIds): void {
                $total = $module->trainers->count();
                $done = $module->trainers->whereIn('id', $completedIds)->count();
                $module->setAttribute('progress', $total > 0 ? (int) round($done / $total * 100) : 0);
                $module->setAttribute('completed_count', $done);
            });

        return view('modules.index', [
            'modules' => $modules,
        ]);
    }

    public function show(Request $request, Module $module): View
    {
        abort_unless($module->is_published, 404);

        $trainers = $module->trainers()
            ->where('is_published', true)
            ->orderBy('sort')
            ->get();

        $attempts = TrainerAttempt::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('trainer_id', $trainers->pluck('id'))
            ->where('status', 'completed')
            ->orderByDesc('completed_at')
            ->get()
            ->unique('trainer_id')
            ->keyBy('trainer_id');

        $done = $trainers->filter(fn ($trainer): bool => $attempts->has($trainer->id))->count();

        return view('modules.show', [
            'module' => $module,
            'trainers' => $trainers,
            'attempts' => $attempts,
            'progress' => $trainers->isEmpty() ? 0 : (int) round($done / $trainers->count() * 100),
        ]);
    }
}
