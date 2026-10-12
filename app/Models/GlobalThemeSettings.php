<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class GlobalThemeSettings extends Model
{
    protected $table = 'global_theme_settings';

    protected $fillable = [
        'id',
        'accent',
        'light_background',
        'light_surface',
        'light_text',
        'dark_background',
        'dark_surface',
        'dark_text',
    ];

    public static function labels(): array
    {
        return [
            'accent' => 'Brand accent',
            'light_background' => 'Light page background',
            'light_surface' => 'Light cards and panels',
            'light_text' => 'Light mode text',
            'dark_background' => 'Dark page background',
            'dark_surface' => 'Dark cards and panels',
            'dark_text' => 'Dark mode text',
        ];
    }

    public static function defaults(): array
    {
        return [
            'accent' => '#c8965f',
            'light_background' => '#f5f0e8',
            'light_surface' => '#ffffff',
            'light_text' => '#1a3a3a',
            'dark_background' => '#0a1f1a',
            'dark_surface' => '#14332c',
            'dark_text' => '#f0ebe0',
        ];
    }

    public static function currentValues(): array
    {
        try {
            return Cache::remember('global-theme-settings.current-values', now()->addMinutes(5), function () {
                return static::loadCurrentValues();
            });
        } catch (\Throwable) {
            return static::loadCurrentValues();
        }
    }

    private static function loadCurrentValues(): array
    {
        try {
            if (! Schema::hasTable((new static)->getTable())) {
                return static::defaults();
            }

            $settings = static::query()->find(1);

            return array_replace(
                static::defaults(),
                $settings?->only(array_keys(static::defaults())) ?? []
            );
        } catch (\Throwable) {
            return static::defaults();
        }
    }

    public static function clearCurrentValuesCache(): void
    {
        try {
            Cache::forget('global-theme-settings.current-values');
        } catch (\Throwable) {
            // Cache is optional; a saved theme remains readable from the database.
        }
    }
}
