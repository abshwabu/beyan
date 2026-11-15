<?php

namespace App\Domain\Finance\Services;

use App\Domain\Institution\Models\Counter;
use App\Domain\Institution\Models\Institution;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinanceNumberGenerator
{
    /**
     * Generate race-safe sequential invoice number (e.g. INV-2026-0001).
     */
    public function nextInvoiceNumber(Institution $institution, ?Carbon $date = null): string
    {
        return $this->nextSequence($institution, 'invoice_number', 'INV', $date);
    }

    /**
     * Generate race-safe sequential receipt/payment number (e.g. RCP-2026-0001).
     */
    public function nextPaymentNumber(Institution $institution, ?Carbon $date = null): string
    {
        return $this->nextSequence($institution, 'payment_number', 'RCP', $date);
    }

    protected function nextSequence(Institution $institution, string $key, string $prefix, ?Carbon $date = null): string
    {
        $year = ($date ?? Carbon::now())->year;

        // Ensure row exists safely
        DB::table('counters')->insertOrIgnore([
            'institution_id' => $institution->id,
            'key' => $key,
            'year' => $year,
            'value' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Atomically increment counter
        Counter::withoutInstitutionScope()
            ->where('institution_id', $institution->id)
            ->where('key', $key)
            ->where('year', $year)
            ->increment('value');

        $sequence = (int) Counter::withoutInstitutionScope()
            ->where('institution_id', $institution->id)
            ->where('key', $key)
            ->where('year', $year)
            ->value('value');

        return sprintf('%s-%d-%04d', $prefix, $year, $sequence);
    }
}
