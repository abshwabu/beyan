<?php

namespace Database\Factories;

use App\Domain\Institution\Models\Institution;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Models\StudentStatusHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentStatusHistoryFactory extends Factory
{
    protected $model = StudentStatusHistory::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'student_id' => Student::factory(),
            'changed_by_id' => null,
            'previous_status' => 'active',
            'new_status' => fake()->randomElement(['inactive', 'suspended', 'withdrawn', 'graduated']),
            'reason' => fake()->sentence(),
            'effective_date' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
        ];
    }
}
