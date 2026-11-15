<?php

namespace Database\Factories;

use App\Domain\Auth\Models\User;
use App\Domain\Finance\Models\Expense;
use App\Domain\Institution\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'category' => fake()->randomElement(['salaries', 'rent', 'utilities', 'maintenance', 'supplies', 'other']),
            'title' => fake()->sentence(3),
            'amount' => fake()->numberBetween(100000, 1000000), // 1,000 to 10,000 ETB
            'date' => fake()->date(),
            'payment_method' => fake()->randomElement(['cash', 'bank_transfer']),
            'reference_number' => fake()->optional()->numerify('EXP-#####'),
            'notes' => fake()->optional()->sentence(),
            'recorded_by' => User::factory(),
        ];
    }
}
