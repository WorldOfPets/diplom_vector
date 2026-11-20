<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'trainer_id', 'attempt_id', 'rating', 'comment'])]
class TrainerFeedback extends Model
{
    protected $table = 'trainer_feedback';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
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

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(TrainerAttempt::class, 'attempt_id');
    }
}
