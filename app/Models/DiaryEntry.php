<?php

namespace App\Models;

use App\Support\DiaryCipher;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'entry_date', 'slot', 'emotion_id', 'intensity', 'cause', 'body_zones', 'note'])]
class DiaryEntry extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'intensity' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function emotion(): BelongsTo
    {
        return $this->belongsTo(Emotion::class);
    }

    protected function cause(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => DiaryCipher::decrypt($this->ownerId(), $value),
            set: fn (?string $value): ?string => DiaryCipher::encrypt($this->ownerId(), $value),
        );
    }

    protected function bodyZones(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?array {
                $decrypted = DiaryCipher::decrypt($this->ownerId(), $value);

                if ($decrypted === null || $decrypted === '') {
                    return null;
                }

                $decoded = json_decode($decrypted, true);

                return is_array($decoded) ? $decoded : null;
            },
            set: function (mixed $value): ?string {
                if ($value === null) {
                    return null;
                }

                return DiaryCipher::encrypt(
                    $this->ownerId(),
                    json_encode(is_array($value) ? array_values($value) : [], JSON_UNESCAPED_UNICODE),
                );
            },
        );
    }

    protected function note(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => DiaryCipher::decrypt($this->ownerId(), $value),
            set: fn (?string $value): ?string => DiaryCipher::encrypt($this->ownerId(), $value),
        );
    }

    private function ownerId(): int
    {
        $userId = $this->user_id ?? auth()->id();

        if ($userId === null) {
            throw new \RuntimeException('Diary entries must belong to a user.');
        }

        return (int) $userId;
    }
}
