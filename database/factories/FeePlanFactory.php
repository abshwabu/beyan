<?php

namespace Database\Factories;

use App\Domain\Finance\Models\FeePlan;
use App\Domain\Institution\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeePlanFactory extends Factory
{
    protected $model = FeePlan::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'course_id' => null,
            'name' => fake()->randomElement(['Hifz Program Monthly Fee', 'Qaida Nooraniyah Term Fee', 'Tajweed & Arabic Monthly Fee']),
            'code' => 'FP-'.fake()->unique()->numerify('###'),
            'period_type' => fake()->randomElement(['monthly', 'term', 'one-time']),
            'amount' => fake()->numberBetween(100000, 300000), // 1,000 to 3,000 ETB
            'currency' => 'ETB',
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
