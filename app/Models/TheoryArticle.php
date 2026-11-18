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
    'cover_path',
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
