<?php

namespace Database\Factories;

use App\Domain\Auth\Models\User;
use App\Domain\Finance\Models\FeeInvoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\Institution\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'fee_invoice_id' => FeeInvoice::factory(),
            'payment_number' => 'RCP-'.fake()->unique()->numerify('#####'),
            'amount' => fake()->numberBetween(50000, 150000),
            'payment_method' => fake()->randomElement(['cash', 'bank_transfer', 'cbe_birr', 'telebirr']),
            'reference_number' => 'REF-'.fake()->numerify('########'),
            'date' => fake()->date(),
            'recorded_by' => User::factory(),
            'idempotency_key' => (string) Str::uuid(),
            'type' => 'payment',
            'reversal_payment_id' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
