<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Workshop-editable settings, layered over config defaults.
 *
 * `config/mquik.php` still declares every key and its default; a row in
 * `app_settings` overrides one. Reading goes through a cache because these are
 * touched on page renders, and the cache is dropped whenever a value is written.
 */
class AppSettings
{
    public const CACHE_KEY = 'app_settings.all';

    /**
     * Read a setting, falling back to the config default.
     *
     * A stored row wins even when it is empty: "" is how the UI expresses a
     * deliberate "off" (e.g. never flag a service with no interval), and must
     * not be mistaken for "unset" and overwritten by the default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $stored = self::all();

        if (array_key_exists($key, $stored)) {
            return $stored[$key] === '' ? null : $stored[$key];
        }

        return $default ?? config('mquik.'.$key);
    }

    /** Integer accessor — most of these are counts and thresholds. */
    public static function int(string $key, ?int $default = null): ?int
    {
        $value = self::get($key, $default);

        return $value === null || $value === '' ? null : (int) $value;
    }

    public static function set(string $key, mixed $value): void
    {
        DB::table('app_settings')->updateOrInsert(
            ['key' => $key],
            [
                // Empty string, not null: the row's existence is what marks the
                // value as deliberately set.
                'value' => $value === null ? '' : (string) $value,
                'updated_by_user_id' => auth()->id(),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, string|null>
     */
    public static function all(): array
    {
        // Tolerate the table not existing yet (fresh clone, mid-migration).
        if (! Schema::hasTable('app_settings')) {
            return [];
        }

        return Cache::rememberForever(self::CACHE_KEY, fn () => DB::table('app_settings')->pluck('value', 'key')->all());
    }
}
