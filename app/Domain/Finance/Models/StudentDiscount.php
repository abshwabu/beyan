<?php

namespace App\Domain\Finance\Models;

use App\Domain\Institution\Audit\Auditable;
use App\Domain\Institution\Tenancy\BelongsToInstitution;
use App\Domain\Students\Models\Student;
use Database\Factories\StudentDiscountFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDiscount extends Model
{
    use Auditable, BelongsToInstitution, HasFactory, HasUlids;

    protected $table = 'student_discounts';

    protected $fillable = [
        'institution_id',
        'student_id',
        'fee_plan_id',
        'name',
        'discount_type', // percentage|fixed_amount
        'discount_value', // 20 for 20%, or minor units for fixed_amount
        'reason',
        'is_active',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'integer',
            'is_active' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    protected static function newFactory(): StudentDiscountFactory
    {
        return StudentDiscountFactory::new();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function feePlan(): BelongsTo
    {
        return $this->belongsTo(FeePlan::class, 'fee_plan_id');
    }

    /**
     * Compute discount amount in minor units against a base amount.
     */
    public function computeDiscount(int $baseAmount): int
    {
        if (! $this->is_active) {
            return 0;
        }

        if ($this->start_date && now()->lt($this->start_date)) {
            return 0;
        }

        if ($this->end_date && now()->gt($this->end_date)) {
            return 0;
        }

        if ($this->discount_type === 'percentage') {
            return (int) round(($baseAmount * min(100, $this->discount_value)) / 100);
        }

        return min($baseAmount, (int) $this->discount_value);
    }
}
