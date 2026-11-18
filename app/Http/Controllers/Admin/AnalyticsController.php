<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EiBranch;
use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Feedback;
use App\Models\Group;
use App\Models\Module;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\TestResponse;
use App\Models\TrainerAttempt;
use App\Models\User;
use App\Services\ContentProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $tests = Test::query()->withCount('items')->orderBy('id')->get();
        $selectedTest = $tests->firstWhere('id', $request->integer('test')) ?? $tests->first();

        $attempts = $selectedTest
            ? TestAttempt::query()
                ->where('test_id', $selectedTest->id)
                ->where('status', 'completed')
                ->get()
            : collect();

        $responses = TestResponse::query()
            ->whereIn('test_attempt_id', $attempts->pluck('id'))
            ->get()
            ->groupBy('test_item_id');

        $itemStats = $selectedTest
            ? $selectedTest->items()->orderBy('sort')->get()->map(function ($item) use ($attempts, $responses): array {
                $rows = $responses->get($item->id, collect());
                $n = $rows->count();
                $p = $n > 0
                    ? round($rows->avg(fn (TestResponse $response): float => (float) $response->max_score > 0
                        ? (float) $response->score / (float) $response->max_score
                        : 0) * 100, 1)
                    : null;

                $pairs = $rows
                    ->map(function (TestResponse $response) use ($attempts): ?array {
                        $attempt = $attempts->firstWhere('id', $response->test_attempt_id);
                        if ($attempt === null) {
                            return null;
                        }

                        return [
                            (float) $response->max_score > 0 ? (float) $response->score / (float) $response->max_score : 0,
                            (float) $attempt->overall,
                        ];
                    })
                    ->filter()
                    ->values();

                return [
                    'item' => $item,
                    'n' => $n,
                    'p' => $p,
                    'r' => $this->correlation($pairs),
                    'avg_time' => $n > 0 ? (int) round($rows->avg('ms')) : null,
                    'flag' => $p !== null && ($p < 20 || $p > 90),
                ];
            })
            : collect();

        $branchMeans = $this->branchMeans($selectedTest, $attempts);

        $moduleStats = Module::query()
            ->orderBy('sort')
            ->withCount(['trainers' => fn ($query) => $query->where('is_published', true)])
            ->get()
            ->map(function (Module $module): array {
                $trainerIds = $module->trainers()->pluck('id');
                $completed = TrainerAttempt::query()
                    ->whereIn('trainer_id', $trainerIds)
                    ->where('status', 'completed')
                    ->count();
                $students = TrainerAttempt::query()
                    ->whereIn('trainer_id', $trainerIds)
                    ->where('status', 'completed')
                    ->distinct('user_id')
                    ->count('user_id');

                return [
                    'module' => $module,
                    'trainers' => $module->trainers_count,
                    'completed' => $completed,
                    'students' => $students,
                ];
            });

        $groupStats = Group::query()
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(function (Group $group): array {
                $userIds = $group->users()->pluck('id');
                $ei = TestAttempt::query()
                    ->whereIn('user_id', $userIds)
                    ->where('status', 'completed')
                    ->whereHas('test', fn ($query) => $query->whereIn('kind', ['ei_full', 'ei_express', 'ei_situational']))
                    ->avg('overall');
                $trainers = TrainerAttempt::query()
                    ->whereIn('user_id', $userIds)
                    ->where('status', 'completed')
                    ->avg('percent');

                $smallGroup = $group->users_count < 3;

                return [
                    'group' => $group,
                    'ei' => ! $smallGroup && $ei !== null ? round((float) $ei, 1) : null,
                    'trainers' => ! $smallGroup && $trainers !== null ? round((float) $trainers, 1) : null,
                ];
            });

        return view('admin.analytics.index', [
            'tests' => $tests,
            'selectedTest' => $selectedTest,
            'itemStats' => $itemStats,
            'branchMeans' => $branchMeans,
            'moduleStats' => $moduleStats,
            'groupStats' => $groupStats,
            'attemptsCount' => $attempts->count(),
            'usersCount' => User::query()->count(),
            'stageStats' => app(ContentProgressService::class)->stageStats(),
            'certificatesCount' => Certificate::query()->count(),
            'feedbackCount' => Feedback::query()->count(),
        ]);
    }

    /**
     * @param  Collection<int, TestAttempt>  $attempts
     * @return Collection<int, array{label: string, value: float|null, color: string}>
     */
    private function branchMeans(?Test $test, Collection $attempts): Collection
    {
        if ($test === null || $attempts->isEmpty()) {
            return collect();
        }

        if ($test->kind === 'motivation') {
            $labels = $test->config['subscales'] ?? [
                'internal' => 'Внутренняя мотивация',
                'external' => 'Внешняя мотивация',
                'professional' => 'Профессиональная направленность',
            ];

            return collect($labels)->map(function (string $label, string $key) use ($attempts): array {
                $values = $attempts->map(fn (TestAttempt $attempt): ?float => $attempt->scores[$key] ?? null)->filter();

                return [
                    'label' => $label,
                    'value' => $values->isEmpty() ? null : round((float) $values->avg(), 1),
                    'color' => '#D97706',
                ];
            })->values();
        }

        return collect(EiBranch::eiBranches())
            ->map(function (EiBranch $branch) use ($attempts): array {
                $values = $attempts->map(fn (TestAttempt $attempt): ?float => $attempt->scores[$branch->value] ?? null)->filter();

                return [
                    'label' => $branch->label(),
                    'value' => $values->isEmpty() ? null : round((float) $values->avg(), 1),
                    'color' => $branch->color(),
                ];
            });
    }

    /**
     * @param  Collection<int, array{0: float, 1: float}>  $pairs
     */
    private function correlation(Collection $pairs): ?float
    {
        $n = $pairs->count();

        if ($n < 3) {
            return null;
        }

        $xs = $pairs->pluck(0);
        $ys = $pairs->pluck(1);
        $meanX = (float) $xs->avg();
        $meanY = (float) $ys->avg();

        $covariance = $pairs->sum(fn (array $pair): float => ($pair[0] - $meanX) * ($pair[1] - $meanY));
        $sdX = sqrt((float) $xs->sum(fn (float $value): float => ($value - $meanX) ** 2));
        $sdY = sqrt((float) $ys->sum(fn (float $value): float => ($value - $meanY) ** 2));

        if ($sdX == 0.0 || $sdY == 0.0) {
            return null;
        }

        return round($covariance / ($sdX * $sdY), 2);
    }
}
