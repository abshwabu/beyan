<?php

namespace App\Domain\Platform\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'platform_settings';

    protected $fillable = [
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    /**
     * Retrieve a setting value with fallback default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Persist or update a setting value.
     */
    public static function set(string $key, mixed $value): static
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Get all platform settings with their default fallbacks.
     *
     * @return array<string, mixed>
     */
    public static function allWithDefaults(): array
    {
        $defaults = [
            'trial_days' => 14,
            'default_grace_days' => 7,
            'default_currency' => 'ETB',
            'supported_languages' => ['en', 'am', 'om', 'ar'],
            'support_phone' => '+251 91 123 4567',
            'support_email' => 'salam@beyan.io',
            'support_telegram' => '@beyan_academy',
            'maintenance_mode' => false,
            'maintenance_message' => 'Beyan is currently undergoing scheduled maintenance. Please check back shortly.',
        ];

        $records = static::all()->pluck('value', 'key')->toArray();

        return array_merge($defaults, $records);
    }
}
