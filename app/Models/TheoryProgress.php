<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'theory_article_id', 'read_at', 'video_progress', 'video_completed_at', 'source_opened_at', 'task_completed_at'])]
class TheoryProgress extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'video_progress' => 'float',
            'video_completed_at' => 'datetime',
            'source_opened_at' => 'datetime',
            'task_completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(TheoryArticle::class, 'theory_article_id');
    }
}
