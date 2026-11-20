<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\Group;
use App\Models\Short;
use App\Models\ShortLike;
use App\Models\ShortView;
use App\Models\TestAttempt;
use App\Models\TheoryArticle;
use App\Models\TheoryProgress;
use App\Models\TrainerAttempt;
use App\Models\TrainerFeedback;
use App\Models\User;
use App\Services\ContentProgressService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function users(): StreamedResponse
    {
        $users = User::query()->with('group')->orderBy('id')->get();

        return $this->csv('users.csv', [
            'id', 'name', 'email', 'role', 'group', 'course', 'specialty', 'consent_at', 'last_seen_at', 'created_at',
        ], $users->map(fn (User $user): array => [
            $user->id,
            $user->name,
            $user->email,
            $user->role?->value,
            $user->group?->name,
            $user->course,
            $user->specialty,
            $user->consent_at?->toDateTimeString(),
            $user->last_seen_at?->toDateTimeString(),
            $user->created_at?->toDateTimeString(),
        ]));
    }

    public function trainerAttempts(): StreamedResponse
    {
        $attempts = TrainerAttempt::query()
            ->where('status', 'completed')
            ->with(['user.group', 'trainer.module'])
            ->orderBy('id')
            ->get();

        return $this->csv('trainer-attempts.csv', [
            'user', 'group', 'module', 'trainer', 'percent', 'passed', 'duration_sec', 'completed_at',
        ], $attempts->map(fn (TrainerAttempt $attempt): array => [
            $attempt->user?->name,
            $attempt->user?->group?->name,
            $attempt->trainer?->module?->title,
            $attempt->trainer?->title,
            $attempt->percent,
            $attempt->passed ? '1' : '0',
            $attempt->duration_sec,
            $attempt->completed_at?->toDateTimeString(),
        ]));
    }

    public function testAttempts(): StreamedResponse
    {
        $attempts = TestAttempt::query()
            ->where('status', 'completed')
            ->with(['user.group', 'test'])
            ->orderBy('id')
            ->get();

        return $this->csv('test-attempts.csv', [
            'user', 'group', 'test', 'overall', 'level', 'scores', 'duration_sec', 'finished_at',
        ], $attempts->map(fn (TestAttempt $attempt): array => [
            $attempt->user?->name,
            $attempt->user?->group?->name,
            $attempt->test?->title,
            $attempt->overall,
            $attempt->level,
            json_encode($attempt->scores, JSON_UNESCAPED_UNICODE),
            $attempt->duration_sec,
            $attempt->finished_at?->toDateTimeString(),
        ]));
    }

    public function progress(): StreamedResponse
    {
        $service = app(ContentProgressService::class);

        $rows = User::query()
            ->where('role', 'student')
            ->with('group')
            ->lazyById(200)
            ->map(function (User $user) use ($service): array {
                $summary = $service->summary($user);
                $categories = collect($summary['categories'])
                    ->mapWithKeys(fn (array $category): array => [$category['key'] => $category['done'].'/'.$category['total']])
                    ->all();

                return [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->group?->name,
                    $user->course,
                    $summary['done'],
                    $summary['total'],
                    $summary['percent'],
                    $summary['remaining'],
                    $summary['certificate_ready'] ? '1' : '0',
                    json_encode($categories, JSON_UNESCAPED_UNICODE),
                    $user->last_seen_at?->toDateTimeString(),
                ];
            });

        return $this->csv('progress.csv', [
            'id', 'name', 'email', 'group', 'course', 'done', 'total', 'percent', 'remaining', 'certificate_ready', 'categories', 'last_seen_at',
        ], $rows);
    }

    public function feedback(): StreamedResponse
    {
        $feedback = TrainerFeedback::query()
            ->with(['user.group', 'trainer.module'])
            ->orderBy('id')
            ->get();

        return $this->csv('trainer-feedback.csv', [
            'user', 'group', 'module', 'trainer', 'rating', 'comment', 'created_at',
        ], $feedback->map(fn (TrainerFeedback $item): array => [
            $item->user?->name,
            $item->user?->group?->name,
            $item->trainer?->module?->title,
            $item->trainer?->title,
            $item->rating,
            $item->comment,
            $item->created_at?->toDateTimeString(),
        ]));
    }

    public function certificates(): StreamedResponse
    {
        $certificates = Certificate::query()
            ->with(['user.group'])
            ->orderBy('id')
            ->get();

        return $this->csv('certificates.csv', [
            'number', 'user', 'group', 'percent', 'issued_at',
        ], $certificates->map(fn (Certificate $certificate): array => [
            $certificate->number,
            $certificate->user?->name,
            $certificate->user?->group?->name,
            $certificate->percent,
            $certificate->issued_at?->toDateTimeString(),
        ]));
    }

    public function groups(): StreamedResponse
    {
        $groups = Group::query()->withCount('users')->orderBy('name')->get();

        return $this->csv('groups.csv', [
            'group', 'students', 'avg_ei', 'avg_trainer_percent', 'trainer_attempts', 'certificates', 'avg_feedback',
        ], $groups->map(function (Group $group): array {
            $userIds = $group->users()->pluck('id');
            $certificates = Certificate::query()->whereIn('user_id', $userIds)->count();
            $feedback = TrainerFeedback::query()->whereIn('user_id', $userIds)->avg('rating');

            return [
                $group->name,
                $group->users_count,
                $this->roundOrNull(
                    TestAttempt::query()
                        ->whereIn('user_id', $userIds)
                        ->where('status', 'completed')
                        ->whereHas('test', fn ($query) => $query->whereIn('kind', ['ei_full', 'ei_express', 'ei_situational']))
                        ->avg('overall'),
                ),
                $this->roundOrNull(
                    TrainerAttempt::query()
                        ->whereIn('user_id', $userIds)
                        ->where('status', 'completed')
                        ->avg('percent'),
                ),
                TrainerAttempt::query()->whereIn('user_id', $userIds)->where('status', 'completed')->count(),
                $certificates,
                $this->roundOrNull($feedback),
            ];
        }));
    }

    public function theory(): StreamedResponse
    {
        $articles = TheoryArticle::query()->published()->orderBy('sort')->get();

        $progress = TheoryProgress::query()
            ->selectRaw('theory_article_id, count(*) as started, sum(case when slides_completed_at is not null or read_at is not null or video_completed_at is not null then 1 else 0 end) as first_step, sum(case when source_opened_at is not null then 1 else 0 end) as source_opened, sum(case when task_completed_at is not null then 1 else 0 end) as task_done')
            ->groupBy('theory_article_id')
            ->get()
            ->keyBy('theory_article_id');

        return $this->csv('theory.csv', [
            'article', 'slides', 'started', 'first_step_done', 'source_opened', 'task_done', 'completion_percent',
        ], $articles->map(function (TheoryArticle $article) use ($progress): array {
            $row = $progress->get($article->id);
            $first = (int) ($row->first_step ?? 0);
            $source = (int) ($row->source_opened ?? 0);
            $task = (int) ($row->task_done ?? 0);
            $started = (int) ($row->started ?? 0);
            $steps = $first + $source + $task;
            $possible = max(1, $started * 3);

            return [
                $article->title,
                count($article->slidesList()),
                $started,
                $first,
                $source,
                $task,
                round($steps / $possible * 100, 1),
            ];
        }));
    }

    public function shorts(): StreamedResponse
    {
        $shorts = Short::query()->published()->orderBy('sort')->get();

        $views = ShortView::query()
            ->selectRaw('short_id, count(*) as started, sum(case when progress >= 80 then 1 else 0 end) as watched')
            ->groupBy('short_id')
            ->get()
            ->keyBy('short_id');

        $likes = ShortLike::query()
            ->selectRaw('short_id, count(*) as total')
            ->groupBy('short_id')
            ->pluck('total', 'short_id');

        return $this->csv('shorts.csv', [
            'title', 'published', 'started', 'watched_80', 'likes', 'watched_percent',
        ], $shorts->map(function (Short $short) use ($views, $likes): array {
            $row = $views->get($short->id);
            $started = (int) ($row->started ?? 0);
            $watched = (int) ($row->watched ?? 0);

            return [
                $short->title,
                $short->created_at?->toDateTimeString(),
                $started,
                $watched,
                (int) ($likes[$short->id] ?? 0),
                $started > 0 ? round($watched / $started * 100, 1) : 0.0,
            ];
        }));
    }

    private function roundOrNull(mixed $value): ?float
    {
        return $value === null ? null : round((float) $value, 1);
    }

    /**
     * @param  list<string>  $headers
     * @param  iterable<array<int, mixed>>  $rows
     */
    private function csv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        AuditLog::record('export.csv', null, ['file' => $filename]);

        return response()->streamDownload(function () use ($headers, $rows): void {
            @set_time_limit(0);

            echo "\xEF\xBB\xBF";

            $output = fopen('php://output', 'w');

            fputcsv($output, $headers, ';');

            foreach ($rows as $row) {
                fputcsv($output, array_map(fn ($value): string => $this->safe($value), $row), ';');
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function safe(mixed $value): string
    {
        $string = (string) ($value ?? '');

        if (preg_match('/^[=+\-@\t\r\n\x0B\x0C]/', $string) === 1) {
            return "'".$string;
        }

        return $string;
    }
}
