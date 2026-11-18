<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'title', 'subtitle', 'description', 'color', 'icon', 'cover_path', 'sort', 'is_published'])]
class Module extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function trainers(): HasMany
    {
        return $this->hasMany(Trainer::class)->orderBy('sort');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function progressFor(User $user): int
    {
        $trainerIds = $this->trainers()->pluck('id');

        if ($trainerIds->isEmpty()) {
            return 0;
        }

        $completed = TrainerAttempt::query()
            ->where('user_id', $user->id)
            ->whereIn('trainer_id', $trainerIds)
            ->where('status', 'completed')
            ->distinct('trainer_id')
            ->count('trainer_id');

        return (int) round($completed / $trainerIds->count() * 100);
    }
}
