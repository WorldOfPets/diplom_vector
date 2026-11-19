<?php

namespace App\Http\Controllers;

use App\Models\CameraProgress;
use App\Models\Module;
use App\Models\Trainer;
use App\Models\TrainerAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
            'cameraProgress' => $this->cameraProgress($request, $trainers),
            'progress' => $trainers->isEmpty() ? 0 : (int) round($done / $trainers->count() * 100),
        ]);
    }

    /**
     * @param  Collection<int, Trainer>  $trainers
     * @return array{done: int, total: int, remaining: list<string>}
     */
    private function cameraProgress(Request $request, Collection $trainers): array
    {
        $cameraTrainer = $trainers->firstWhere('engine', 'camera');

        if ($cameraTrainer === null) {
            return ['done' => 0, 'total' => 0, 'remaining' => []];
        }

        $targets = collect($cameraTrainer->config['targets'] ?? []);
        $targetIds = $targets->pluck('id')->all();

        $completed = CameraProgress::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('emotion', $targetIds)
            ->distinct()
            ->pluck('emotion')
            ->all();

        return [
            'done' => count($completed),
            'total' => count($targetIds),
            'remaining' => $targets
                ->reject(fn (array $target): bool => in_array($target['id'], $completed, true))
                ->pluck('label')
                ->values()
                ->all(),
        ];
    }
}
