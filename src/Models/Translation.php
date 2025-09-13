<?php

namespace Jawabapp\Localization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Jawabapp\Localization\Models\Translation
 *
 * @property int $id
 * @property string $locale
 * @property string $group
 * @property string $key
 * @property string|null $value
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Translation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'translations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'locale',
        'group',
        'key',
        'value',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the table name
     */
    public function getTable()
    {
        return config('localization.database.table', 'translations');
    }

    /**
     * Get the database connection
     */
    public function getConnectionName()
    {
        return config('localization.database.connection');
    }

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Clear cache when translations are modified
        static::saved(function ($translation) {
            $translation->clearCache();
        });

        static::deleted(function ($translation) {
            $translation->clearCache();
        });
    }

    /**
     * Clear translation cache
     */
    public function clearCache(): void
    {
        if (config('localization.cache.enabled', true)) {
            $prefix = config('localization.cache.key_prefix', 'localization');
            Cache::forget("{$prefix}.{$this->locale}");
            Cache::forget("{$prefix}.{$this->locale}.{$this->group}");
        }
    }

    /**
     * Scope to filter by locale
     */
    public function scopeLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }

    /**
     * Scope to filter by group
     */
    public function scopeGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }

    /**
     * Scope to filter by key
     */
    public function scopeKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }

    /**
     * Scope to search translations
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('key', 'like', "%{$search}%")
              ->orWhere('value', 'like', "%{$search}%");
        });
    }

    /**
     * Scope to get untranslated items
     */
    public function scopeUntranslated(Builder $query): Builder
    {
        return $query->whereNull('value')->orWhere('value', '');
    }

    /**
     * Get all translations for a locale
     */
    public static function getTranslationsForLocale(string $locale): array
    {
        if (config('localization.cache.enabled', true)) {
            $prefix = config('localization.cache.key_prefix', 'localization');
            $duration = config('localization.cache.duration', 60 * 24);

            return Cache::remember(
                "{$prefix}.{$locale}",
                now()->addMinutes($duration),
                fn() => self::fetchTranslationsForLocale($locale)
            );
        }

        return self::fetchTranslationsForLocale($locale);
    }

    /**
     * Fetch translations for a locale from database
     */
    protected static function fetchTranslationsForLocale(string $locale): array
    {
        $translations = [];

        $items = self::locale($locale)->get();

        foreach ($items as $item) {
            if ($item->group === '__JSON__') {
                $translations[$item->key] = $item->value;
            } else {
                self::arraySet($translations, "{$item->group}.{$item->key}", $item->value);
            }
        }

        return $translations;
    }

    /**
     * Get all translations for a group and locale
     */
    public static function getTranslationsForGroup(string $group, string $locale): array
    {
        if (config('localization.cache.enabled', true)) {
            $prefix = config('localization.cache.key_prefix', 'localization');
            $duration = config('localization.cache.duration', 60 * 24);

            return Cache::remember(
                "{$prefix}.{$locale}.{$group}",
                now()->addMinutes($duration),
                fn() => self::fetchTranslationsForGroup($group, $locale)
            );
        }

        return self::fetchTranslationsForGroup($group, $locale);
    }

    /**
     * Fetch translations for a group from database
     */
    protected static function fetchTranslationsForGroup(string $group, string $locale): array
    {
        $translations = [];

        $items = self::locale($locale)->group($group)->get();

        foreach ($items as $item) {
            self::arraySet($translations, $item->key, $item->value);
        }

        return $translations;
    }

    /**
     * Import translations from array
     */
    public static function import(array $translations, string $locale, string $group = null, bool $overwrite = false): int
    {
        $count = 0;

        foreach ($translations as $key => $value) {
            if (is_array($value)) {
                $count += self::importNested($value, $locale, $group, $key, $overwrite);
            } else {
                $translation = self::firstOrNew([
                    'locale' => $locale,
                    'group' => $group ?? '__JSON__',
                    'key' => $key,
                ]);

                if ($overwrite || !$translation->exists || empty($translation->value)) {
                    $translation->value = $value;
                    $translation->save();
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Import nested translations
     */
    protected static function importNested(array $translations, string $locale, string $group, string $prefix, bool $overwrite): int
    {
        $count = 0;

        foreach ($translations as $key => $value) {
            $fullKey = "{$prefix}.{$key}";

            if (is_array($value)) {
                $count += self::importNested($value, $locale, $group, $fullKey, $overwrite);
            } else {
                $translation = self::firstOrNew([
                    'locale' => $locale,
                    'group' => $group,
                    'key' => $fullKey,
                ]);

                if ($overwrite || !$translation->exists || empty($translation->value)) {
                    $translation->value = $value;
                    $translation->save();
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Helper to set array values using dot notation
     */
    protected static function arraySet(&$array, $key, $value): void
    {
        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;
    }

    /**
     * Get statistics for translations
     */
    public static function getStatistics(): array
    {
        $stats = [];

        $locales = config('localization.supported_locales', ['en']);

        foreach ($locales as $locale) {
            $total = self::locale($locale)->count();
            $translated = self::locale($locale)->whereNotNull('value')->where('value', '!=', '')->count();

            $stats[$locale] = [
                'total' => $total,
                'translated' => $translated,
                'untranslated' => $total - $translated,
                'percentage' => $total > 0 ? round(($translated / $total) * 100, 2) : 0,
            ];
        }

        return $stats;
    }

    /**
     * Copy translations from one locale to another
     */
    public static function copyLocale(string $fromLocale, string $toLocale, bool $overwrite = false): int
    {
        $count = 0;

        $translations = self::locale($fromLocale)->get();

        foreach ($translations as $translation) {
            $newTranslation = self::firstOrNew([
                'locale' => $toLocale,
                'group' => $translation->group,
                'key' => $translation->key,
            ]);

            if ($overwrite || !$newTranslation->exists || empty($newTranslation->value)) {
                $newTranslation->value = $translation->value;
                $newTranslation->metadata = $translation->metadata;
                $newTranslation->save();
                $count++;
            }
        }

        return $count;
    }
}