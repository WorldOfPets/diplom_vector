<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'test_id',
    'user_id',
    'form',
    'status',
    'scores',
    'draft',
    'overall',
    'level',
    'duration_sec',
    'started_at',
    'finished_at',
])]
class TestAttempt extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scores' => 'array',
            'draft' => 'array',
            'overall' => 'decimal:2',
            'duration_sec' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(TestResponse::class);
    }

    public function branchScore(string $branch): ?float
    {
        $value = $this->scores[$branch] ?? null;

        return $value === null ? null : (float) $value;
    }
}
