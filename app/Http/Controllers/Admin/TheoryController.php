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
        $article = TheoryArticle::query()->create($this->validated($request));
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
        $article->update($this->validated($request, $article));
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
            'source_url' => ['nullable', 'url', 'max:255'],
            'reading_minutes' => ['required', 'integer', 'between:1,120'],
            'sort' => ['required', 'integer', 'between:0,1000'],
            'is_published' => ['nullable', 'boolean'],
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
}
