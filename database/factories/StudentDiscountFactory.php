<?php

namespace Database\Factories;

use App\Domain\Finance\Models\StudentDiscount;
use App\Domain\Institution\Models\Institution;
use App\Domain\Students\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentDiscountFactory extends Factory
{
    protected $model = StudentDiscount::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'student_id' => Student::factory(),
            'fee_plan_id' => null,
            'name' => fake()->randomElement(['Sibling Discount (20%)', 'Need-Based Scholarship', 'Orphan Aid']),
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'reason' => fake()->optional()->sentence(),
            'is_active' => true,
            'start_date' => null,
            'end_date' => null,
        ];
    }
}
