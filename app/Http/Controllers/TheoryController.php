<?php

namespace App\Http\Controllers;

use App\Models\TheoryArticle;
use App\Models\TheoryProgress;
use App\Services\ContentProgressService;
use Illuminate\Http\JsonResponse;
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

        $readIds = TheoryProgress::query()
            ->where('user_id', $request->user()->id)
            ->whereNotNull('read_at')
            ->pluck('theory_article_id');

        return view('theory.index', [
            'articles' => $articles,
            'readIds' => $readIds,
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
            'next' => TheoryArticle::query()
                ->published()
                ->where('sort', '>', $article->sort)
                ->orderBy('sort')
                ->first(),
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
}
