<?php

namespace Database\Factories;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Term;
use App\Domain\Institution\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Term>
 */
class TermFactory extends Factory
{
    protected $model = Term::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'name' => 'Term '.fake()->numberBetween(1, 4),
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->addMonths(3)->endOfMonth()->toDateString(),
            'is_current' => false,
        ];
    }

    public function current(): static
    {
        return $this->state(fn () => ['is_current' => true]);
    }
}
