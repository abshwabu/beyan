<?php

namespace Tests\Feature;

use App\Domain\Auth\Models\User;
use App\Domain\Institution\Models\Institution;
use App\Support\DirectionHelper;
use App\Support\TimezoneHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class I18nAndRtlTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_four_required_locales_have_translations(): void
    {
        $locales = ['en', 'am', 'om', 'ar'];
        $requiredKeys = [
            'madrasa_management',
            'institution',
            'academic_years',
            'terms',
            'students',
            'teachers',
            'attendance',
            'hifz',
            'finance',
            'reports',
            'dashboard',
            'direction',
        ];

        foreach ($locales as $locale) {
            app()->setLocale($locale);

            $this->assertFileExists(base_path("lang/{$locale}/app.php"), "Language file lang/{$locale}/app.php must exist.");
            $this->assertFileExists(base_path("lang/{$locale}.json"), "JSON language file lang/{$locale}.json must exist.");

            foreach ($requiredKeys as $key) {
                $translated = trans("app.{$key}");
                $this->assertNotEmpty($translated, "Locale [{$locale}] is missing translation for key [app.{$key}].");
                $this->assertNotEquals("app.{$key}", $translated, "Locale [{$locale}] translation for [app.{$key}] should not return the key literal.");
            }
        }
    }

    public function test_arabic_returns_rtl_direction_and_others_return_ltr(): void
    {
        $this->assertEquals('rtl', DirectionHelper::forLocale('ar'));
        $this->assertTrue(DirectionHelper::isRtl('ar'));

        $this->assertEquals('ltr', DirectionHelper::forLocale('en'));
        $this->assertFalse(DirectionHelper::isRtl('en'));

        $this->assertEquals('ltr', DirectionHelper::forLocale('am'));
        $this->assertFalse(DirectionHelper::isRtl('am'));

        $this->assertEquals('ltr', DirectionHelper::forLocale('om'));
        $this->assertFalse(DirectionHelper::isRtl('om'));
    }

    public function test_middleware_switches_locale_to_institution_preference(): void
    {
        $institutionAr = Institution::factory()->create([
            'locale' => 'ar',
            'status' => 'active',
        ]);

        $userAr = User::withoutInstitutionScope()->create([
            'institution_id' => $institutionAr->id,
            'name' => 'Arabic Ustadh',
            'email' => 'ustadh@arabic.edu.et',
            'password' => bcrypt('password'),
            'role' => 'owner',
        ]);

        $this->actingAs($userAr)->get(route('dashboard'));

        $this->assertEquals('ar', app()->getLocale(), 'Application locale must match the institution locale.');
        $this->assertEquals('rtl', DirectionHelper::forLocale(), 'Direction for Arabic institution must be RTL.');
    }

    public function test_user_session_locale_overrides_institution_default_locale(): void
    {
        $institutionAr = Institution::factory()->create([
            'locale' => 'ar',
            'status' => 'active',
        ]);

        $userAr = User::withoutInstitutionScope()->create([
            'institution_id' => $institutionAr->id,
            'name' => 'Arabic Ustadh',
            'email' => 'ustadh2@arabic.edu.et',
            'password' => bcrypt('password'),
            'role' => 'owner',
        ]);

        $this->actingAs($userAr)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'));

        $this->assertEquals('en', app()->getLocale(), 'User session locale must override institution default locale.');
        $this->assertEquals('ltr', DirectionHelper::forLocale(), 'Direction for English session must be LTR.');
    }

    public function test_utc_dates_are_converted_to_institution_timezone(): void
    {
        // 12:00:00 UTC corresponds to 15:00:00 (3:00 PM) in Africa/Addis_Ababa (UTC+3)
        $utcTime = Carbon::create(2026, 11, 15, 12, 0, 0, 'UTC');

        $converted = TimezoneHelper::toInstitutionTimezone(
            date: $utcTime,
            timezone: 'Africa/Addis_Ababa',
            format: 'Y-m-d H:i:s'
        );

        $this->assertEquals('2026-11-15 15:00:00', $converted, 'UTC 12:00 should convert to 15:00 in Africa/Addis_Ababa timezone.');

        // Test reverse: local time to UTC
        $localTime = '2026-11-15 15:00:00';
        $utcResult = TimezoneHelper::toUtc($localTime, 'Africa/Addis_Ababa');

        $this->assertEquals('2026-11-15 12:00:00', $utcResult->format('Y-m-d H:i:s'), 'Local time 15:00 in Africa/Addis_Ababa must convert to 12:00 UTC.');
    }
}
