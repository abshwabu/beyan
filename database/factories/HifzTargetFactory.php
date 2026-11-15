<?php

namespace Database\Factories;

use App\Domain\Hifz\Models\HifzTarget;
use App\Domain\Institution\Models\Institution;
use App\Domain\Students\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class HifzTargetFactory extends Factory
{
    protected $model = HifzTarget::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'student_id' => Student::factory(),
            'period' => fake()->randomElement(['daily', 'weekly']),
            'target_type' => fake()->randomElement(['sabaq_ayahs', 'sabaq_pages', 'sabqi_pages', 'manzil_pages']),
            'target_value' => fake()->numberBetween(3, 20),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function daily(): static
    {
        return $this->state(fn (array $attributes) => ['period' => 'daily']);
    }

    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => ['period' => 'weekly']);
    }
}
