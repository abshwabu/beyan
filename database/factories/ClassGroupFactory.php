<?php

namespace Database\Factories;

use App\Domain\Academics\Models\ClassGroup;
use App\Domain\Academics\Models\Course;
use App\Domain\Institution\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassGroupFactory extends Factory
{
    protected $model = ClassGroup::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'course_id' => Course::factory(),
            'academic_term_id' => null,
            'name' => fake()->randomElement(['Morning Hifz A', 'Evening Tajweed B', 'Weekend Arabic 1', 'Afternoon Qaida']),
            'capacity' => 15,
            'type' => fake()->randomElement(['physical', 'online', 'hybrid']),
            'meeting_link' => 'https://meet.google.com/xyz-test',
            'room' => 'Room 101',
            'status' => 'active',
        ];
    }
}
