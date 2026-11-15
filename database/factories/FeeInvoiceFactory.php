<?php

namespace Database\Factories;

use App\Domain\Finance\Models\FeeInvoice;
use App\Domain\Institution\Models\Institution;
use App\Domain\Students\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeeInvoiceFactory extends Factory
{
    protected $model = FeeInvoice::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 500, 5000);

        return [
            'institution_id' => Institution::factory(),
            'student_id' => Student::factory(),
            'invoice_number' => 'INV-'.fake()->unique()->numberBetween(10000, 99999),
            'title' => 'Term Tuition Fee',
            'amount' => $amount,
            'paid_amount' => 0,
            'status' => 'pending',
            'due_date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
        ];
    }
}
