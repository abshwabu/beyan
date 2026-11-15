<?php

namespace Database\Factories;

use App\Domain\Finance\Models\FeeInvoice;
use App\Domain\Finance\Models\InvoiceItem;
use App\Domain\Institution\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        $amount = fake()->numberBetween(50000, 200000);

        return [
            'institution_id' => Institution::factory(),
            'fee_invoice_id' => FeeInvoice::factory(),
            'fee_plan_id' => null,
            'description' => 'Tuition Item',
            'amount' => $amount,
            'discount_amount' => 0,
            'final_amount' => $amount,
        ];
    }
}
