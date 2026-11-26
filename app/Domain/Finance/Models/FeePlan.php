<?php

namespace App\Domain\Finance\Models;

use App\Domain\Academics\Models\Course;
use App\Domain\Institution\Audit\Auditable;
use App\Domain\Institution\Tenancy\BelongsToInstitution;
use App\Domain\Students\Models\Student;
use Database\Factories\FeePlanFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeePlan extends Model
{
    use Auditable, BelongsToInstitution, HasFactory, HasUlids;

    protected $table = 'fee_plans';

    protected $fillable = [
        'institution_id',
        'course_id',
        'name',
        'code',
        'period_type', // monthly|term|one-time
        'amount', // integer minor units (e.g. cents)
        'currency',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): FeePlanFactory
    {
        return FeePlanFactory::new();
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(FeeInvoice::class, 'fee_plan_id');
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(StudentDiscount::class, 'fee_plan_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'fee_plan_id');
    }

    /**
     * Get human-formatted amount string.
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount / 100, 2).' '.$this->currency;
    }
}
