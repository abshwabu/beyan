<?php

namespace Database\Factories;

use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Institution\Models\Institution;
use App\Domain\Students\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'student_id' => Student::factory(),
            'date' => fake()->date(),
            'status' => fake()->randomElement(['present', 'absent', 'late', 'excused']),
            'remarks' => fake()->sentence(),
            'recorded_by' => null,
        ];
    }
}
