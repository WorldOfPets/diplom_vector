<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'module_id',
    'slug',
    'title',
    'subtitle',
    'description',
    'cover_path',
    'engine',
    'config',
    'settings',
    'points',
    'duration_min',
    'version',
    'sort',
    'is_published',
])]
class Trainer extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'config' => 'array',
            'settings' => 'array',
            'points' => 'integer',
            'duration_min' => 'integer',
            'version' => 'integer',
            'sort' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(TrainerAttempt::class);
    }

    /**
     * Config safe to send to the browser: scoring keys are stripped.
     *
     * @return array<string, mixed>
     */
    public function publicConfig(): array
    {
        $config = $this->config ?? [];
        unset($config['scoring'], $config['key']);

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    public function scoringConfig(): array
    {
        return $this->config['scoring'] ?? [];
    }
}
