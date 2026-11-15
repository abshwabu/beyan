<?php

namespace Database\Factories;

use App\Domain\Academics\Models\Course;
use App\Domain\Institution\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $courses = [
            ['name' => 'Qaida Nooraniyah', 'code' => 'QAI-101', 'level' => 'beginner'],
            ['name' => "Qur'an Reading", 'code' => 'QUR-101', 'level' => 'elementary'],
            ['name' => 'Tajweed Rules', 'code' => 'TAJ-201', 'level' => 'intermediate'],
            ['name' => 'Hifz Program', 'code' => 'HIFZ-100', 'level' => 'all_levels'],
            ['name' => 'Islamic Studies', 'code' => 'ISL-101', 'level' => 'beginner'],
            ['name' => 'Arabic Language', 'code' => 'ARB-101', 'level' => 'intermediate'],
        ];

        $choice = fake()->randomElement($courses);
        $code = $choice['code'].'-'.fake()->unique()->numberBetween(100, 999);

        return [
            'institution_id' => Institution::factory(),
            'name' => $choice['name'],
            'code' => $code,
            'level' => $choice['level'],
            'duration' => '3 months',
            'learning_objectives' => [
                'Master Quranic phonetics and letter recognition',
                'Proper application of basic articulation points',
            ],
            'description' => fake()->sentence(),
            'status' => 'active',
        ];
    }
}
