<?php

namespace App\Http\Controllers;

use App\Models\Short;
use App\Models\ShortLike;
use App\Models\ShortView;
use App\Services\ContentProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ShortController extends Controller
{
    public function index(Request $request): View
    {
        $shorts = Short::query()
            ->published()
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        $likedIds = ShortLike::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('short_id', $shorts->pluck('id'))
            ->pluck('short_id')
            ->flip();

        $viewedIds = ShortView::query()
            ->where('user_id', $request->user()->id)
            ->where('progress', '>=', 80)
            ->whereIn('short_id', $shorts->pluck('id'))
            ->pluck('short_id')
            ->flip();

        return view('shorts.index', [
            'shorts' => $shorts,
            'likedIds' => $likedIds,
            'viewedIds' => $viewedIds,
        ]);
    }

    public function view(Request $request, Short $short): JsonResponse
    {
        abort_unless($short->is_published, 404);

        $data = $request->validate([
            'percent' => ['required', 'numeric', 'min:80', 'max:100'],
        ]);

        $view = ShortView::query()->firstOrNew([
            'short_id' => $short->id,
            'user_id' => $request->user()->id,
        ]);

        $view->fill([
            'viewed_at' => $view->viewed_at ?? now(),
            'progress' => max((float) ($view->progress ?? 0), (float) $data['percent']),
        ])->save();

        app(ContentProgressService::class)->sync($request->user());

        return response()->json([
            'viewed' => true,
            'percent' => (float) $view->progress,
        ]);
    }

    public function like(Request $request, Short $short): JsonResponse
    {
        abort_unless($short->is_published, 404);

        $userId = $request->user()->id;

        $result = DB::transaction(function () use ($short, $userId): array {
            $like = ShortLike::query()
                ->where('short_id', $short->id)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if ($like !== null) {
                $like->delete();
                $short->decrement('likes_count');
                $liked = false;
            } else {
                ShortLike::query()->create([
                    'short_id' => $short->id,
                    'user_id' => $userId,
                ]);
                $short->increment('likes_count');
                $liked = true;
            }

            $short->refresh();

            return [
                'liked' => $liked,
                'likes' => max(0, (int) $short->likes_count),
            ];
        });

        return response()->json($result);
    }
}
