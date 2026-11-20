<?php

namespace App\Http\Controllers;

use App\Models\TheoryArticle;
use App\Models\TheoryProgress;
use App\Services\ContentProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TheoryController extends Controller
{
    public function index(Request $request): View
    {
        $articles = TheoryArticle::query()
            ->published()
            ->orderBy('sort')
            ->get();

        $progress = TheoryProgress::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('theory_article_id', $articles->pluck('id'))
            ->get()
            ->keyBy('theory_article_id');

        $stepsTotal = $articles->count() * 3;
        $stepsDone = $articles->sum(fn (TheoryArticle $article): int => $article->completedSteps($progress->get($article->id)));

        return view('theory.index', [
            'articles' => $articles,
            'progress' => $progress,
            'stepsDone' => $stepsDone,
            'stepsTotal' => $stepsTotal,
        ]);
    }

    public function show(Request $request, TheoryArticle $article): View
    {
        abort_unless($article->is_published, 404);

        $progress = TheoryProgress::query()->firstOrNew([
            'user_id' => $request->user()->id,
            'theory_article_id' => $article->id,
        ]);

        return view('theory.show', [
            'article' => $article,
            'isRead' => $article->isReadBy($request->user()),
            'videoCompleted' => $progress->video_completed_at !== null,
            'videoProgress' => (float) ($progress->video_progress ?? 0),
            'slides' => array_map(static fn (string $path): string => asset($path), $article->slidesList()),
            'slidesProgress' => is_array($progress->slides_progress) ? $progress->slides_progress : [],
            'slidesCompleted' => $progress->slides_completed_at !== null,
            'sourceOpened' => $progress->source_opened_at !== null,
            'taskCompleted' => $progress->task_completed_at !== null,
            'completedSteps' => $article->completedSteps($progress),
            'next' => TheoryArticle::query()
                ->published()
                ->where('sort', '>', $article->sort)
                ->orderBy('sort')
                ->first(),
        ]);
    }

    public function markSlide(Request $request, TheoryArticle $article): JsonResponse
    {
        abort_unless($article->is_published && $article->hasSlides(), 404);

        $data = $request->validate([
            'index' => ['required', 'integer', 'min:0'],
            'seconds' => ['required', 'integer', 'min:1', 'max:120'],
        ]);

        $total = count($article->slidesList());

        abort_if($data['index'] >= $total, 422);

        $progress = TheoryProgress::query()->firstOrNew([
            'user_id' => $request->user()->id,
            'theory_article_id' => $article->id,
        ]);

        $viewed = is_array($progress->slides_progress) ? $progress->slides_progress : [];
        $key = (string) $data['index'];
        $viewed[$key] = max((int) ($viewed[$key] ?? 0), (int) $data['seconds']);

        $minimum = 15;
        $completed = true;

        for ($i = 0; $i < $total; $i++) {
            if ((int) ($viewed[(string) $i] ?? 0) < $minimum) {
                $completed = false;

                break;
            }
        }

        $progress->slides_progress = $viewed;

        if ($completed && $progress->slides_completed_at === null) {
            $progress->slides_completed_at = now();
            $progress->read_at = $progress->read_at ?? now();
        }

        $progress->save();

        if ($completed) {
            app(ContentProgressService::class)->sync($request->user());
        }

        $seen = count(array_filter($viewed, static fn (mixed $value): bool => (int) $value >= $minimum));

        return response()->json([
            'ok' => true,
            'viewed' => $seen,
            'total' => $total,
            'completed' => $completed,
        ]);
    }

    public function markRead(Request $request, TheoryArticle $article): JsonResponse
    {
        abort_unless($article->is_published, 404);

        TheoryProgress::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'theory_article_id' => $article->id,
            ],
            ['read_at' => now()],
        );

        app(ContentProgressService::class)->sync($request->user());

        return response()->json(['ok' => true]);
    }

    public function markVideo(Request $request, TheoryArticle $article): JsonResponse
    {
        abort_unless($article->is_published && $article->hasTrackableVideo(), 404);

        $data = $request->validate([
            'percent' => ['required', 'numeric', 'min:80', 'max:100'],
        ]);

        $progress = TheoryProgress::query()->firstOrNew([
            'user_id' => $request->user()->id,
            'theory_article_id' => $article->id,
        ]);

        $progress->fill([
            'read_at' => $progress->read_at ?? now(),
            'video_progress' => max((float) ($progress->video_progress ?? 0), (float) $data['percent']),
            'video_completed_at' => $progress->video_completed_at ?? now(),
        ])->save();

        app(ContentProgressService::class)->sync($request->user());

        return response()->json([
            'ok' => true,
            'percent' => (float) $progress->video_progress,
        ]);
    }

    public function openSource(Request $request, TheoryArticle $article): RedirectResponse
    {
        abort_unless($article->is_published, 404);

        $sourceUrl = (string) $article->source_url;

        if (! str_starts_with($sourceUrl, 'http://') && ! str_starts_with($sourceUrl, 'https://')) {
            return redirect()
                ->route('theory.show', $article)
                ->withErrors(['source' => 'У этого материала пока нет доступного первоисточника.']);
        }

        $progress = TheoryProgress::query()->firstOrNew([
            'user_id' => $request->user()->id,
            'theory_article_id' => $article->id,
        ]);

        if ($progress->source_opened_at === null) {
            $progress->source_opened_at = now();
            $progress->save();
            app(ContentProgressService::class)->sync($request->user());
        }

        $encodedSourceUrl = preg_replace_callback(
            '/[^\x20-\x7E]/u',
            static fn (array $matches): string => rawurlencode($matches[0]),
            $sourceUrl,
        ) ?? $sourceUrl;

        return redirect()->away($encodedSourceUrl);
    }

    public function completeTask(Request $request, TheoryArticle $article): JsonResponse
    {
        abort_unless($article->is_published && $article->hasTask(), 404);

        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable', 'string', 'max:120'],
        ]);

        $expected = $article->task['answers'] ?? [];
        $submitted = $data['answers'] ?? [];

        $normalize = static fn (mixed $value): string => mb_strtolower(trim((string) $value));

        if (count($submitted) !== count($expected)) {
            return response()->json([
                'message' => 'Заполните все пропуски.',
                'ok' => false,
            ], 422);
        }

        foreach ($expected as $key => $answer) {
            if (! array_key_exists($key, $submitted) || $normalize($submitted[$key]) !== $normalize($answer)) {
                return response()->json([
                    'message' => 'Есть слова не на своих местах. Попробуйте ещё раз.',
                    'ok' => false,
                ], 422);
            }
        }

        $progress = TheoryProgress::query()->firstOrNew([
            'user_id' => $request->user()->id,
            'theory_article_id' => $article->id,
        ]);

        if ($progress->task_completed_at === null) {
            $progress->task_completed_at = now();
            $progress->save();
            app(ContentProgressService::class)->sync($request->user());
        }

        return response()->json([
            'ok' => true,
            'completed' => true,
            'message' => 'Задание выполнено: все слова на своих местах.',
        ]);
    }
}
