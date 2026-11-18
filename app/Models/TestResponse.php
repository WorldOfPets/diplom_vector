<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['test_attempt_id', 'test_item_id', 'response', 'score', 'max_score', 'ms'])]
class TestResponse extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'response' => 'array',
            'score' => 'decimal:3',
            'max_score' => 'decimal:3',
            'ms' => 'integer',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(TestAttempt::class, 'test_attempt_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(TestItem::class, 'test_item_id');
    }
}
