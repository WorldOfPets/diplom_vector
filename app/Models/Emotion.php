<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'group', 'parent_slug', 'color', 'emoji', 'description', 'sort'])]
class Emotion extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort' => 'integer',
        ];
    }

    public function diaryEntries(): HasMany
    {
        return $this->hasMany(DiaryEntry::class);
    }

    public function parent(): ?self
    {
        return $this->parent_slug === null
            ? null
            : static::query()->where('slug', $this->parent_slug)->first();
    }
}
