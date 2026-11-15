<?php

namespace App\Support;

class DirectionHelper
{
    /**
     * Get text direction for a given locale.
     */
    public static function forLocale(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        return in_array($locale, ['ar', 'fa', 'ur', 'he'], true) ? 'rtl' : 'ltr';
    }

    /**
     * Check if a given locale is RTL.
     */
    public static function isRtl(?string $locale = null): bool
    {
        return static::forLocale($locale) === 'rtl';
    }
}
