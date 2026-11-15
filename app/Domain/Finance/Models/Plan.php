<?php

namespace App\Domain\Finance\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'plans';

    protected $fillable = [
        'name',
        'slug',
        'monthly_price',
        'yearly_price',
        'currency',
        'student_limit',
        'user_limit',
        'features',
        'badge',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_active' => 'boolean',
            'monthly_price' => 'integer',
            'yearly_price' => 'integer',
            'student_limit' => 'integer',
            'user_limit' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get monthly price in major units (e.g. 600.00).
     */
    public function getMonthlyPriceMajorAttribute(): float
    {
        return $this->monthly_price / 100;
    }

    /**
     * Get yearly price in major units (e.g. 6000.00).
     */
    public function getYearlyPriceMajorAttribute(): float
    {
        return $this->yearly_price / 100;
    }

    /**
     * Calculate yearly savings percentage compared to 12 months.
     */
    public function getYearlySavingsPercentageAttribute(): int
    {
        $annualMonthly = $this->monthly_price * 12;
        if ($annualMonthly <= 0 || $this->yearly_price >= $annualMonthly) {
            return 0;
        }

        return (int) round((($annualMonthly - $this->yearly_price) / $annualMonthly) * 100);
    }
}
