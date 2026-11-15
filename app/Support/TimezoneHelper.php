<?php

namespace App\Support;

use App\Domain\Institution\Tenancy\TenantManager;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;

class TimezoneHelper
{
    /**
     * Convert a UTC date/time to the institution's timezone.
     */
    public static function toInstitutionTimezone(
        CarbonInterface|DateTimeInterface|string|null $date,
        ?string $timezone = null,
        ?string $format = null
    ): ?string {
        if (! $date) {
            return null;
        }

        if (! $timezone) {
            /** @var TenantManager $tenantManager */
            $tenantManager = app(TenantManager::class);
            $timezone = $tenantManager->getTenant()?->timezone ?: 'Africa/Addis_Ababa';
        }

        $carbon = $date instanceof CarbonInterface
            ? $date->copy()
            : Carbon::parse($date, 'UTC');

        $converted = $carbon->setTimezone($timezone);

        return $format ? $converted->format($format) : $converted->toIso8601String();
    }

    /**
     * Convert a local date/time in the institution's timezone to UTC for database storage.
     */
    public static function toUtc(
        CarbonInterface|DateTimeInterface|string|null $date,
        ?string $sourceTimezone = null
    ): ?Carbon {
        if (! $date) {
            return null;
        }

        if (! $sourceTimezone) {
            /** @var TenantManager $tenantManager */
            $tenantManager = app(TenantManager::class);
            $sourceTimezone = $tenantManager->getTenant()?->timezone ?: 'Africa/Addis_Ababa';
        }

        return Carbon::parse($date, $sourceTimezone)->setTimezone('UTC');
    }
}
