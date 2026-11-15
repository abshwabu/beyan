<?php

namespace Database\Factories;

use App\Domain\Institution\Models\Institution;
use App\Domain\Teachers\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'user_id' => null,
            'full_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'qualifications' => ['Ijazah in Hafs an Asim', 'BA in Islamic Studies'],
            'specialization' => fake()->randomElement(['Hifz', 'Tajweed', 'Arabic', 'Islamic Studies']),
            'status' => 'active',
            'availability_slots' => [
                ['weekday' => 1, 'start_time' => '08:00', 'end_time' => '12:00'],
                ['weekday' => 3, 'start_time' => '08:00', 'end_time' => '12:00'],
            ],
            'bio' => fake()->paragraph(),
        ];
    }
}
