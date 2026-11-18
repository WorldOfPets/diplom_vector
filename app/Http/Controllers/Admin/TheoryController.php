<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\TheoryArticle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TheoryController extends Controller
{
    public function index(): View
    {
        return view('admin.theory.index', [
            'articles' => TheoryArticle::query()
                ->withCount('progress')
                ->orderBy('sort')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.theory.form', [
            'article' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $article = TheoryArticle::query()->create($this->withUploadedVideo($request, $this->validated($request)));
        AuditLog::record('theory.created', $article);

        return redirect()->route('admin.theory.index')->with('status', 'Материал создан.');
    }

    public function edit(TheoryArticle $article): View
    {
        return view('admin.theory.form', [
            'article' => $article,
        ]);
    }

    public function update(Request $request, TheoryArticle $article): RedirectResponse
    {
        $previousVideo = $article->video_path;
        $data = $this->withUploadedVideo($request, $this->validated($request, $article));
        $article->update($data);

        if ($previousVideo !== $article->video_path) {
            $this->deleteVideo($previousVideo);
        }

        AuditLog::record('theory.updated', $article);

        return redirect()->route('admin.theory.index')->with('status', 'Материал обновлён.');
    }

    public function toggle(TheoryArticle $article): RedirectResponse
    {
        $article->update(['is_published' => ! $article->is_published]);
        AuditLog::record('theory.toggled', $article, ['published' => $article->is_published]);

        return back()->with('status', 'Публикация изменена.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?TheoryArticle $article = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:180'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'cover_path' => ['nullable', 'string', 'max:255'],
            'video_url' => ['nullable', 'url', 'max:255'],
            'video_file' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm', 'mimes:mp4,webm', 'max:204800'],
            'remove_video' => ['nullable', 'boolean'],
            'source_url' => ['nullable', 'url', 'max:255'],
            'reading_minutes' => ['required', 'integer', 'between:1,120'],
            'sort' => ['required', 'integer', 'between:0,1000'],
            'is_published' => ['nullable', 'boolean'],
        ], [
            'video_file.mimetypes' => 'Загрузите видео в формате MP4 или WebM.',
            'video_file.mimes' => 'Загрузите видео в формате MP4 или WebM.',
            'video_file.max' => 'Видео должно быть не больше 200 МБ.',
        ]);

        $base = Str::slug($data['slug'] ?? $data['title'], '-', 'ru');
        if ($base === '') {
            $base = 'article-'.time();
        }

        $slug = $base;
        $index = 2;
        while (TheoryArticle::query()
            ->where('slug', $slug)
            ->when($article, fn ($query) => $query->where('id', '!=', $article->id))
            ->exists()) {
            $slug = $base.'-'.$index++;
        }

        return [
            'title' => $data['title'],
            'slug' => $slug,
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['body'] ?? null,
            'cover_path' => $data['cover_path'] ?? null,
            'video_url' => $data['video_url'] ?? null,
            'source_url' => $data['source_url'] ?? null,
            'reading_minutes' => $data['reading_minutes'],
            'sort' => $data['sort'],
            'is_published' => $request->boolean('is_published'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withUploadedVideo(Request $request, array $data): array
    {
        if ($request->boolean('remove_video')) {
            $data['video_path'] = null;

            return $data;
        }

        $file = $request->file('video_file');

        if ($file === null) {
            return $data;
        }

        $directory = public_path('media/theory');

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), '-', 'ru');
        $base = $base !== '' ? Str::limit($base, 100, '') : 'theory-video';
        $extension = strtolower($file->getClientOriginalExtension() ?: 'mp4');
        $name = $base.'-'.Str::lower(Str::random(8)).'.'.$extension;

        $file->move($directory, $name);

        $data['video_path'] = 'media/theory/'.$name;

        return $data;
    }

    private function deleteVideo(?string $path): void
    {
        if ($path === null || ! str_starts_with($path, 'media/theory/')) {
            return;
        }

        $absolute = public_path($path);

        if (is_file($absolute)) {
            unlink($absolute);
        }
    }
}
