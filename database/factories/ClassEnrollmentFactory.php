<?php

namespace Database\Factories;

use App\Domain\Academics\Models\ClassEnrollment;
use App\Domain\Academics\Models\ClassGroup;
use App\Domain\Institution\Models\Institution;
use App\Domain\Students\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassEnrollmentFactory extends Factory
{
    protected $model = ClassEnrollment::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'student_id' => Student::factory(),
            'class_group_id' => ClassGroup::factory(),
            'start_date' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'end_date' => null,
            'status' => 'enrolled',
            'notes' => fake()->sentence(),
        ];
    }
}
