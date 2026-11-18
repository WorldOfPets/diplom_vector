<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Feedback;
use App\Models\ProgressSnapshot;
use App\Models\Short;
use App\Models\ShortView;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\TheoryArticle;
use App\Models\TheoryProgress;
use App\Models\Trainer;
use App\Models\TrainerAttempt;
use App\Models\User;

class ContentProgressService
{
    public const CERTIFICATE_THRESHOLD = 95.0;

    /**
     * @return array{
     *     categories: array<int, array{key: string, label: string, hint: string, done: int, total: int, percent: float, color: string, icon: string}>,
     *     done: int,
     *     total: int,
     *     percent: float,
     *     remaining: int,
     *     certificate_threshold: float,
     *     certificate_ready: bool
     * }
     */
    public function summary(User $user): array
    {
        $trainerIds = Trainer::query()
            ->where('is_published', true)
            ->whereHas('module', fn ($query) => $query->where('is_published', true))
            ->pluck('id');

        $theoryArticles = TheoryArticle::query()->published()->get(['id', 'video_url', 'video_path']);
        $theoryIds = $theoryArticles->pluck('id');
        $testIds = Test::query()->where('is_active', true)->pluck('id');
        $shortIds = Short::query()->published()->pluck('id');

        $trainersDone = TrainerAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereIn('trainer_id', $trainerIds)
            ->distinct()
            ->count('trainer_id');

        $theoryDone = TheoryProgress::query()
            ->where('user_id', $user->id)
            ->whereNotNull('read_at')
            ->whereIn('theory_article_id', $theoryIds)
            ->get()
            ->filter(function (TheoryProgress $progress) use ($theoryArticles): bool {
                $article = $theoryArticles->firstWhere('id', $progress->theory_article_id);

                return $article !== null
                    && (! $article->hasTrackableVideo() || $progress->video_completed_at !== null);
            })
            ->count();

        $testsDone = TestAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereIn('test_id', $testIds)
            ->distinct()
            ->count('test_id');

        $shortsDone = ShortView::query()
            ->where('user_id', $user->id)
            ->where('progress', '>=', 80)
            ->whereIn('short_id', $shortIds)
            ->distinct()
            ->count('short_id');

        $categories = [
            $this->category('shorts', 'Просмотрено', 'мини-видео', $shortsDone, $shortIds->count(), 'var(--color-coral-ink)', 'play'),
            $this->category('theory', 'Изучено', 'статьи теории', $theoryDone, $theoryIds->count(), 'var(--color-amber-ink)', 'book'),
            $this->category('trainers', 'Пройдено', 'тренажёры', $trainersDone, $trainerIds->count(), 'var(--color-brand-deep)', 'sparkles'),
            $this->category('tests', 'Диагностировано', 'тесты и кейсы', $testsDone, $testIds->count(), 'var(--color-lilac-ink)', 'chart'),
        ];

        $done = array_sum(array_column($categories, 'done'));
        $total = array_sum(array_column($categories, 'total'));
        $percent = $total > 0 ? round($done / $total * 100, 1) : 0.0;
        $needed = (int) ceil($total * self::CERTIFICATE_THRESHOLD / 100);

        return [
            'categories' => $categories,
            'done' => $done,
            'total' => $total,
            'percent' => $percent,
            'remaining' => max(0, $needed - $done),
            'certificate_threshold' => self::CERTIFICATE_THRESHOLD,
            'certificate_ready' => $percent >= self::CERTIFICATE_THRESHOLD,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function sync(User $user): array
    {
        $summary = $this->summary($user);

        $this->snapshot($user, 'initial', $summary);

        if ($summary['percent'] >= 50) {
            $this->snapshot($user, 'fifty', $summary);
        }

        if ($summary['percent'] >= 90) {
            $this->snapshot($user, 'ninety', $summary);
        }

        $summary['snapshots'] = ProgressSnapshot::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('stage');

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    private function snapshot(User $user, string $stage, array $summary): void
    {
        ProgressSnapshot::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'stage' => $stage,
            ],
            [
                'percent' => $summary['percent'],
                'counts' => [
                    'categories' => $summary['categories'],
                    'done' => $summary['done'],
                    'total' => $summary['total'],
                ],
                'captured_at' => now(),
            ],
        );
    }

    public function issueCertificate(User $user): ?Certificate
    {
        $existing = Certificate::query()->where('user_id', $user->id)->first();

        if ($existing !== null) {
            return $existing;
        }

        $summary = $this->summary($user);

        if ($summary['percent'] < self::CERTIFICATE_THRESHOLD) {
            return null;
        }

        if (! Feedback::query()->where('user_id', $user->id)->exists()) {
            return null;
        }

        return Certificate::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'number' => $this->certificateNumber($user),
                'percent' => $summary['percent'],
                'issued_at' => now(),
                'meta' => [
                    'done' => $summary['done'],
                    'total' => $summary['total'],
                    'categories' => $summary['categories'],
                ],
            ],
        );
    }

    /**
     * @return array<string, array{users: int, avg_percent: float}>
     */
    public function stageStats(): array
    {
        $rows = ProgressSnapshot::query()
            ->selectRaw('stage, COUNT(*) as users, AVG(percent) as avg_percent')
            ->groupBy('stage')
            ->get()
            ->keyBy('stage');

        $stats = [];

        foreach (['initial', 'fifty', 'ninety'] as $stage) {
            $row = $rows->get($stage);

            $stats[$stage] = [
                'users' => $row !== null ? (int) $row->users : 0,
                'avg_percent' => $row !== null ? round((float) $row->avg_percent, 1) : 0.0,
            ];
        }

        return $stats;
    }

    /**
     * @return array{key: string, label: string, hint: string, done: int, total: int, percent: float, color: string, icon: string}
     */
    private function category(string $key, string $label, string $hint, int $done, int $total, string $color, string $icon): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'hint' => $hint,
            'done' => $done,
            'total' => $total,
            'percent' => $total > 0 ? round($done / $total * 100, 1) : 0.0,
            'color' => $color,
            'icon' => $icon,
        ];
    }

    private function certificateNumber(User $user): string
    {
        return 'VK-'.now()->format('Y').'-'.str_pad((string) $user->id, 5, '0', STR_PAD_LEFT);
    }
}
