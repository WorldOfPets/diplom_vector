<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['trainer_attempt_id', 'metric_key', 'metric_value', 'metric_label'])]
class AttemptMetric extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metric_value' => 'decimal:3',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(TrainerAttempt::class, 'trainer_attempt_id');
    }
}
