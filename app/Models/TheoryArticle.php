<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug',
    'title',
    'excerpt',
    'body',
    'task',
    'cover_path',
    'slides',
    'video_url',
    'video_path',
    'source_url',
    'reading_minutes',
    'sort',
    'is_published',
])]
class TheoryArticle extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'task' => 'array',
            'slides' => 'array',
            'reading_minutes' => 'integer',
            'sort' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function progress(): HasMany
    {
        return $this->hasMany(TheoryProgress::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function isReadBy(User $user): bool
    {
        return $this->progress()->where('user_id', $user->id)->whereNotNull('read_at')->exists();
    }

    public function rutubeEmbedUrl(): ?string
    {
        if (! $this->video_url || ! preg_match('~rutube\.ru/(?:video|play/embed)/([A-Za-z0-9_-]+)~', $this->video_url, $matches)) {
            return null;
        }

        return 'https://rutube.ru/play/embed/'.$matches[1];
    }

    public function hasVideo(): bool
    {
        return filled($this->video_url) || filled($this->video_path);
    }

    /**
     * @return list<string>
     */
    public function slidesList(): array
    {
        return is_array($this->slides) ? array_values(array_filter($this->slides, 'is_string')) : [];
    }

    public function hasSlides(): bool
    {
        return $this->slidesList() !== [];
    }

    public function coverSlide(): ?string
    {
        return $this->slidesList()[0] ?? null;
    }

    public function hasTask(): bool
    {
        return is_array($this->task) && filled($this->task['text'] ?? null) && filled($this->task['answers'] ?? null);
    }

    public function firstStepCompleted(?TheoryProgress $progress): bool
    {
        if ($progress === null) {
            return false;
        }

        if ($this->hasSlides()) {
            return $progress->slides_completed_at !== null;
        }

        return $this->hasTrackableVideo()
            ? $progress->video_completed_at !== null
            : $progress->read_at !== null;
    }

    public function completedSteps(?TheoryProgress $progress): int
    {
        if ($progress === null) {
            return 0;
        }

        return (int) $this->firstStepCompleted($progress)
            + (int) ($progress->source_opened_at !== null)
            + (int) ($progress->task_completed_at !== null);
    }

    public function localVideoPath(): ?string
    {
        return filled($this->video_path) ? $this->video_path : null;
    }

    public function localVideoMime(): string
    {
        return str_ends_with(strtolower((string) $this->video_path), '.webm') ? 'video/webm' : 'video/mp4';
    }

    public function localVideoPoster(): ?string
    {
        if ($this->localVideoPath() === null) {
            return null;
        }

        $poster = preg_replace('/\.(mp4|webm)$/i', '.jpg', $this->localVideoPath());

        return $poster !== null && is_file(public_path($poster)) ? $poster : null;
    }

    public function hasTrackableVideo(): bool
    {
        return $this->rutubeEmbedUrl() !== null || $this->localVideoPath() !== null;
    }
}
