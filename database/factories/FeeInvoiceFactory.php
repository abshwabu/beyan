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
        $totalAmount = fake()->numberBetween(50000, 300000); // 500 to 3000 ETB in minor units

        return [
            'institution_id' => Institution::factory(),
            'student_id' => Student::factory(),
            'fee_plan_id' => null,
            'invoice_number' => 'INV-'.fake()->unique()->numerify('#####'),
            'title' => 'Term Tuition Fee',
            'period' => '2026-11',
            'subtotal' => $totalAmount,
            'discount_total' => 0,
            'total_amount' => $totalAmount,
            'amount' => $totalAmount / 100,
            'paid_amount' => 0,
            'balance_due' => $totalAmount,
            'status' => 'unpaid',
            'due_date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
        ];
    }
}
