<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'trainer_id',
    'trainer_version',
    'status',
    'response',
    'config_snapshot',
    'metrics',
    'score',
    'max_score',
    'percent',
    'passed',
    'feedback',
    'duration_sec',
    'started_at',
    'completed_at',
])]
class TrainerAttempt extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'response' => 'array',
            'config_snapshot' => 'array',
            'metrics' => 'array',
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'percent' => 'decimal:2',
            'passed' => 'boolean',
            'trainer_version' => 'integer',
            'duration_sec' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(AttemptMetric::class);
    }

    public function reflection()
    {
        return $this->hasOne(Reflection::class);
    }
}
