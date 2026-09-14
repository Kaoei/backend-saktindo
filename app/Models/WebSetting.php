<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class WebSetting extends Model
{
    protected $table = 'web_settings';

    protected $primaryKey = 'key';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    private static ?\Illuminate\Support\Collection $runtimeCache = null;

    protected static function booted()
    {
        static::saved(function () {
            self::clearCache();
        });
        static::deleted(function () {
            self::clearCache();
        });
    }

    public static function allCached(): \Illuminate\Support\Collection
    {
        if (self::$runtimeCache !== null) {
            return self::$runtimeCache;
        }

        try {
            self::$runtimeCache = Cache::rememberForever('backend_web_settings_all', function () {
                return static::pluck('value', 'key');
            });
        } catch (\Throwable $e) {
            self::$runtimeCache = static::pluck('value', 'key');
        }

        return self::$runtimeCache ?? collect();
    }

    public static function clearCache(): void
    {
        self::$runtimeCache = null;
        try {
            Cache::forget('backend_web_settings_all');
            Cache::forget('backend_web_customization');
        } catch (\Throwable $e) {}
    }

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $settings = static::allCached();
        return $settings->has($key) ? $settings->get($key) : $default;
    }

    public static function setValue(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        self::clearCache();
    }
}