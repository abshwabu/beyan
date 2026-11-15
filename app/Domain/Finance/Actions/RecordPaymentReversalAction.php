<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Models\FeeInvoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Services\FinanceNumberGenerator;
use App\Domain\Institution\Models\Institution;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordPaymentReversalAction
{
    public function __construct(
        protected FinanceNumberGenerator $numberGenerator
    ) {}

    /**
     * Issue an append-only payment reversal or refund, adjusting invoice balance atomically.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function execute(array $data): Payment
    {
        $originalPaymentId = $data['original_payment_id'];
        $idempotencyKey = trim((string) ($data['idempotency_key'] ?? ''));

        if (empty($idempotencyKey)) {
            throw ValidationException::withMessages([
                'idempotency_key' => ['An idempotency key is required for reversal.'],
            ]);
        }

        /** @var Payment $originalPayment */
        $originalPayment = Payment::query()->findOrFail($originalPaymentId);

        if ($originalPayment->isReversal()) {
            throw ValidationException::withMessages([
                'original_payment_id' => ['Cannot reverse or refund an existing reversal/refund record.'],
            ]);
        }

        // Idempotency check
        $existing = Payment::query()
            ->where('institution_id', $originalPayment->institution_id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing) {
            return $existing->load(['invoice.student', 'recorder', 'originalPayment']);
        }

        // Calculate maximum refundable amount on this specific payment
        $alreadyRefunded = (int) $originalPayment->reversals()->sum('amount');
        $maxRefundable = $originalPayment->amount - $alreadyRefunded;

        $refundAmount = isset($data['amount_minor_units'])
            ? (int) $data['amount_minor_units']
            : (isset($data['amount']) ? (int) round(((float) $data['amount']) * 100) : $maxRefundable);

        if ($refundAmount <= 0 || $refundAmount > $maxRefundable) {
            throw ValidationException::withMessages([
                'amount' => ["Refund amount must be between 1 and {$maxRefundable} minor units."],
            ]);
        }

        /** @var Institution $institution */
        $institution = $originalPayment->institution ?? Institution::findOrFail($originalPayment->institution_id);

        return DB::transaction(function () use (
            $institution,
            $originalPayment,
            $refundAmount,
            $idempotencyKey,
            $data
        ) {
            // Lock invoice for recalculation
            $invoice = FeeInvoice::query()->lockForUpdate()->findOrFail($originalPayment->fee_invoice_id);

            $paymentNumber = $this->numberGenerator->nextPaymentNumber($institution);

            /** @var Payment $reversal */
            $reversal = Payment::create([
                'institution_id' => $institution->id,
                'fee_invoice_id' => $invoice->id,
                'payment_number' => $paymentNumber,
                'amount' => $refundAmount,
                'payment_method' => $originalPayment->payment_method,
                'reference_number' => $data['reference_number'] ?? "REV-{$originalPayment->payment_number}",
                'date' => $data['date'] ?? now()->toDateString(),
                'recorded_by' => $data['recorded_by'],
                'idempotency_key' => $idempotencyKey,
                'type' => $data['type'] ?? 'reversal', // refund|reversal
                'reversal_payment_id' => $originalPayment->id,
                'notes' => $data['notes'] ?? $data['reason'] ?? 'Payment correction/reversal',
            ]);

            // Recompute balance inside the same transaction
            $invoice->recalculateBalance();

            return $reversal->load(['invoice.student', 'recorder', 'originalPayment']);
        });
    }
}
