<?php

namespace Database\Factories;

use App\Domain\Hifz\Models\HifzEntry;
use App\Domain\Institution\Models\Institution;
use App\Domain\Students\Models\Student;
use App\Domain\Teachers\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class HifzEntryFactory extends Factory
{
    protected $model = HifzEntry::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'student_id' => Student::factory(),
            'teacher_id' => Teacher::factory(),
            'class_session_id' => null,
            'date' => fake()->date(),
            'type' => fake()->randomElement(['sabaq', 'sabqi', 'manzil', 'reading']),
            'surah_number' => 1,
            'start_ayah' => 1,
            'end_ayah' => 7,
            'quality_rating' => fake()->randomElement(['excellent', 'very_good', 'good', 'acceptable', 'poor']),
            'tajweed_rating' => fake()->randomElement(['excellent', 'good', 'needs_work']),
            'mistakes_count' => fake()->numberBetween(0, 3),
            'teacher_comment' => fake()->optional()->sentence(),
        ];
    }

    public function sabaq(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'sabaq']);
    }

    public function sabqi(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'sabqi']);
    }

    public function manzil(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'manzil']);
    }

    public function reading(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'reading']);
    }
}
