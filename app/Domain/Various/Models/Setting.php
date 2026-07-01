<?php

declare(strict_types=1);

namespace App\Domain\Various\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public $incrementing = false;
    protected $primaryKey = 'key';
    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'string',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting.{$key}", 86400, function () use ($key, $default) {
            $setting = static::find($key);

            if (! $setting) {
                return $default;
            }

            $decoded = json_decode((string) $setting->value, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $setting->value;
        });
    }

    public static function set(string $key, mixed $value, ?string $description = null): void
    {
        $encoded = is_string($value) ? $value : json_encode($value);

        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $encoded,
                'description' => $description,
            ]
        );

        Cache::forget("setting.{$key}");
    }
}
