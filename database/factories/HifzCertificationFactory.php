<?php

namespace Database\Factories;

use App\Domain\Hifz\Models\HifzCertification;
use App\Domain\Institution\Models\Institution;
use App\Domain\Students\Models\Student;
use App\Domain\Teachers\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class HifzCertificationFactory extends Factory
{
    protected $model = HifzCertification::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'student_id' => Student::factory(),
            'teacher_id' => Teacher::factory(),
            'type' => fake()->randomElement(['hifz_completion', 'juz_completion', 'ijazah', 'tajweed_certificate']),
            'title' => fake()->sentence(3),
            'riwayah' => 'Hafs an Asim',
            'sanad_chain' => fake()->optional()->paragraph(),
            'completion_date' => fake()->date(),
            'certificate_number' => 'CERT-'.fake()->unique()->numerify('#####'),
            'grade' => 'Mumtaz',
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function ijazah(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'ijazah',
            'title' => 'Ijazah in Hafs an Asim',
            'riwayah' => 'Hafs an Asim',
            'sanad_chain' => 'Connected chain back to Prophet Muhammad (peace be upon him)',
        ]);
    }

    public function hifzCompletion(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'hifz_completion',
            'title' => 'Full Qur\'an Memorization Completion',
        ]);
    }
}
