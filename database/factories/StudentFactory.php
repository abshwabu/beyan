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
        $code = 'BYN-2026-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 4, '0', STR_PAD_LEFT);
        $name = fake()->name();

        return [
            'institution_id' => Institution::factory(),
            'full_name' => $name,
            'name' => $name,
            'student_code' => $code,
            'admission_number' => $code,
            'gender' => fake()->randomElement(['male', 'female']),
            'date_of_birth' => fake()->dateTimeBetween('-18 years', '-5 years')->format('Y-m-d'),
            'contact' => [
                'phone' => fake()->phoneNumber(),
                'email' => fake()->safeEmail(),
                'address' => fake()->address(),
            ],
            'admission_date' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'status' => 'active',
        ];
    }
}
