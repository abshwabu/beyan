<?php

namespace App\Domain\Platform\Actions;

use App\Domain\Platform\Models\PlatformPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReversePlatformPaymentAction
{
    /**
     * Reverse an existing subscription payment with audited reason.
     */
    public function execute(string $paymentId, string $reason, string $reversedBy): PlatformPayment
    {
        $payment = PlatformPayment::findOrFail($paymentId);

        if ($payment->isReversed()) {
            throw new InvalidArgumentException('This payment has already been reversed.');
        }

        return DB::transaction(function () use ($payment, $reason, $reversedBy) {
            $payment->update([
                'status' => 'reversed',
                'reversal_reason' => $reason,
                'reversed_at' => Carbon::now(),
                'reversed_by' => $reversedBy,
            ]);

            $payment->recordAudit('payment_reversed', ['status' => 'completed'], [
                'status' => 'reversed',
                'reason' => $reason,
                'reversed_by' => $reversedBy,
            ]);

            return $payment;
        });
    }
}
