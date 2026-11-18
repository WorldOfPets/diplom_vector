<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'title', 'subtitle', 'description', 'kind', 'time_limit_min', 'config', 'is_active', 'version'])]
class Test extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_active' => 'boolean',
            'time_limit_min' => 'integer',
            'version' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(TestItem::class)->orderBy('sort');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(TestAttempt::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isEi(): bool
    {
        return str_starts_with($this->kind, 'ei_');
    }
}
