<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::rememberForever('setting:'.$key, fn (): ?self => static::query()->find($key));

        if ($setting === null || $setting->value === null) {
            return $default;
        }

        return json_decode($setting->value, true) ?? $setting->value;
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => json_encode($value, JSON_UNESCAPED_UNICODE)],
        );

        Cache::forget('setting:'.$key);
    }
}
