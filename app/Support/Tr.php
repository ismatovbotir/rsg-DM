<?php

namespace App\Support;

/**
 * Translation of dynamic strings. Uzbek is the source language, so the keys of lang/ru.json are the Uzbek texts.
 * Blade echoes go through t() (see AppServiceProvider), which swaps a value that is exactly a known text.
 */
class Tr
{
    /** @var array<string,string>|null */
    private static ?array $map = null;

    private static string $mapLocale = '';

    public static function t(mixed $value): mixed
    {
        if (! is_string($value) || $value === '' || app()->getLocale() === config('app.fallback_locale')) {
            return $value;
        }

        if (self::$mapLocale !== app()->getLocale()) {
            $file = lang_path(app()->getLocale().'.json');
            self::$map = is_file($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
            self::$mapLocale = app()->getLocale();
        }

        return self::$map[$value] ?? $value;
    }

    /** Languages the interface speaks: code => name (shown in their own language). */
    public static function locales(): array
    {
        return config('app.locales');
    }
}
