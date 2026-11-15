<?php

namespace Database\Factories;

use App\Domain\Institution\Models\Institution;
use App\Domain\Students\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'name' => fake()->name(),
            'admission_number' => 'ADM-'.fake()->unique()->numberBetween(1000, 9999),
            'gender' => fake()->randomElement(['male', 'female']),
            'date_of_birth' => fake()->dateTimeBetween('-18 years', '-5 years')->format('Y-m-d'),
            'status' => 'active',
        ];
    }
}
