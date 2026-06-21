<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * @return array<string, string>
     */
    protected $casts = [
        'value' => 'string',
    ];

    public static function getValue(string $key, string $default = ''): string
    {
        return Cache::remember("system_settings:{$key}", 3600, function () use ($key, $default): string {
            $setting = self::query()->where('key', $key)->first();

            return $setting?->value ?? $default;
        });
    }

    public static function setValue(string $key, string $value): SystemSetting
    {
        Cache::forget("system_settings:{$key}");

        return self::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }
}
