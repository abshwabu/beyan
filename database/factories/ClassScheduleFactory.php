<?php

namespace Database\Factories;

use App\Domain\Academics\Models\ClassGroup;
use App\Domain\Academics\Models\ClassSchedule;
use App\Domain\Institution\Models\Institution;
use App\Domain\Teachers\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassScheduleFactory extends Factory
{
    protected $model = ClassSchedule::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'class_group_id' => ClassGroup::factory(),
            'teacher_id' => Teacher::factory(),
            'weekday' => fake()->numberBetween(0, 6),
            'start_time' => '09:00',
            'end_time' => '10:30',
            'timezone' => 'Africa/Addis_Ababa',
        ];
    }
}
