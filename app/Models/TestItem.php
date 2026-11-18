<?php

namespace App\Models;

use App\Support\PhotoLibrary;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

#[Fillable(['test_id', 'branch', 'type', 'prompt', 'media_path', 'config', 'key_data', 'weight', 'difficulty', 'sort', 'is_active'])]
class TestItem extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'config' => 'array',
            'key_data' => 'array',
            'weight' => 'decimal:2',
            'difficulty' => 'integer',
            'sort' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    /**
     * Data safe to send to the browser: no answer key.
     *
     * @return array<string, mixed>
     */
    public function publicData(): array
    {
        $config = $this->config ?? [];

        if ($this->type === 'motivation') {
            unset($config['reverse']);
        }

        if ($this->type === 'best_worst' && isset($config['options']) && is_array($config['options'])) {
            $config['options'] = array_map(
                fn (mixed $option): mixed => is_array($option) ? Arr::except($option, ['scores']) : $option,
                $config['options'],
            );
        }

        return [
            'id' => $this->id,
            'branch' => $this->branch,
            'type' => $this->type,
            'prompt' => $this->prompt,
            'media_path' => $this->media_path,
            'media_credit' => PhotoLibrary::credit($this->media_path),
            'config' => $config,
            'weight' => (float) $this->weight,
        ];
    }
}
