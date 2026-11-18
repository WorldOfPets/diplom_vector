<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug',
    'title',
    'subtitle',
    'topic',
    'video_path',
    'poster_path',
    'duration_sec',
    'likes_count',
    'sort',
    'is_published',
])]
class Short extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_sec' => 'integer',
            'likes_count' => 'integer',
            'sort' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function likes(): HasMany
    {
        return $this->hasMany(ShortLike::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function likedBy(User $user): bool
    {
        return $this->likes()->where('user_id', $user->id)->exists();
    }
}
