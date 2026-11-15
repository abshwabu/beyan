<?php

namespace Database\Factories;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Institution\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->numberBetween(2023, 2030);

        return [
            'institution_id' => Institution::factory(),
            'name' => "{$year}-".($year + 1),
            'start_date' => "{$year}-09-01",
            'end_date' => ($year + 1).'-06-30',
            'is_current' => false,
        ];
    }

    public function current(): static
    {
        return $this->state(fn () => ['is_current' => true]);
    }
}
