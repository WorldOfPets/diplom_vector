<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Trainer;
use App\Models\TrainerAttempt;
use App\Models\TrainerFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'trainer' => $request->integer('trainer') ?: null,
            'module' => $request->integer('module') ?: null,
            'rating' => $request->integer('rating') ?: null,
            'only_comments' => $request->boolean('comments'),
        ];

        $base = TrainerFeedback::query();

        $total = (clone $base)->count();
        $average = $total > 0 ? round((float) (clone $base)->avg('rating'), 2) : null;
        $low = (clone $base)->where('rating', '<', 3)->count();
        $withComment = (clone $base)->whereNotNull('comment')->where('comment', '!=', '')->count();

        $distribution = (clone $base)
            ->selectRaw('rating, count(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $completedAttempts = TrainerAttempt::query()->where('status', 'completed')->count();
        $responseRate = $completedAttempts > 0 ? round($total / $completedAttempts * 100, 1) : 0.0;

        $feedback = TrainerFeedback::query()
            ->with(['user.group', 'trainer.module'])
            ->when($filters['trainer'], fn ($query, int $trainer) => $query->where('trainer_id', $trainer))
            ->when($filters['module'], fn ($query, int $module) => $query->whereHas(
                'trainer',
                fn ($inner) => $inner->where('module_id', $module),
            ))
            ->when($filters['rating'], fn ($query, int $rating) => $query->where('rating', $rating))
            ->when($filters['only_comments'], fn ($query) => $query->whereNotNull('comment')->where('comment', '!=', ''))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $trainerStats = $this->trainerStats();

        return view('admin.feedback.index', [
            'feedback' => $feedback,
            'trainers' => Trainer::query()->orderBy('title')->get(['id', 'title']),
            'modules' => Module::query()->orderBy('sort')->get(['id', 'title']),
            'filters' => $filters,
            'total' => $total,
            'average' => $average,
            'low' => $low,
            'withComment' => $withComment,
            'distribution' => $distribution,
            'responseRate' => $responseRate,
            'completedAttempts' => $completedAttempts,
            'trainerStats' => $trainerStats,
        ]);
    }

    /**
     * @return Collection<int, array{trainer: Trainer, count: int, average: float, low: int}>
     */
    private function trainerStats(): Collection
    {
        $rows = TrainerFeedback::query()
            ->selectRaw('trainer_id, count(*) as total, avg(rating) as average, sum(case when rating < 3 then 1 else 0 end) as low')
            ->groupBy('trainer_id')
            ->orderByDesc('total')
            ->get();

        $trainers = Trainer::query()
            ->with('module')
            ->whereIn('id', $rows->pluck('trainer_id'))
            ->get()
            ->keyBy('id');

        return $rows
            ->map(function (TrainerFeedback $row) use ($trainers): ?array {
                $trainer = $trainers->get($row->trainer_id);

                if ($trainer === null) {
                    return null;
                }

                return [
                    'trainer' => $trainer,
                    'count' => (int) $row->total,
                    'average' => round((float) $row->average, 2),
                    'low' => (int) $row->low,
                ];
            })
            ->filter()
            ->values();
    }
}
