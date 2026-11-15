<?php

namespace Database\Factories;

use App\Domain\Institution\Models\Institution;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Models\StudentEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentEnrollmentFactory extends Factory
{
    protected $model = StudentEnrollment::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'student_id' => Student::factory(),
            'academic_year_id' => null,
            'class_name' => fake()->randomElement(['Hifz Level 1', 'Hifz Level 2', 'Tajweed Foundation', 'Arabic Elementary']),
            'start_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'end_date' => null,
            'status' => 'current',
            'notes' => fake()->sentence(),
        ];
    }
}
