<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Models\FeeInvoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Services\FinanceNumberGenerator;
use App\Domain\Institution\Models\Institution;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordPaymentAction
{
    public function __construct(
        protected FinanceNumberGenerator $numberGenerator
    ) {}

    /**
     * Record a payment against an invoice idempotently, computing outstanding balance atomically.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function execute(array $data): Payment
    {
        $invoiceId = $data['fee_invoice_id'];
        $idempotencyKey = trim((string) ($data['idempotency_key'] ?? ''));

        if (empty($idempotencyKey)) {
            throw ValidationException::withMessages([
                'idempotency_key' => ['An idempotency key is required to record a payment.'],
            ]);
        }

        /** @var FeeInvoice $invoice */
        $invoice = FeeInvoice::query()->findOrFail($invoiceId);

        // 1. Idempotency Check: if payment with this key already exists, return it safely without duplicate
        $existing = Payment::query()
            ->where('institution_id', $invoice->institution_id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing) {
            return $existing->load(['invoice.student', 'recorder']);
        }

        // Amount in minor units (e.g. cents)
        $amount = isset($data['amount_minor_units'])
            ? (int) $data['amount_minor_units']
            : (int) round(((float) $data['amount']) * 100);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Payment amount must be greater than zero.'],
            ]);
        }

        /** @var Institution $institution */
        $institution = $invoice->institution ?? Institution::findOrFail($invoice->institution_id);

        return DB::transaction(function () use ($institution, $invoice, $amount, $data, $idempotencyKey) {
            // Lock invoice for write inside the immediate transaction
            $lockedInvoice = FeeInvoice::query()->lockForUpdate()->findOrFail($invoice->id);

            $paymentNumber = $this->numberGenerator->nextPaymentNumber($institution);

            /** @var Payment $payment */
            $payment = Payment::create([
                'institution_id' => $institution->id,
                'fee_invoice_id' => $lockedInvoice->id,
                'payment_number' => $paymentNumber,
                'amount' => $amount,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'reference_number' => $data['reference_number'] ?? null,
                'date' => $data['date'] ?? now()->toDateString(),
                'recorded_by' => $data['recorded_by'],
                'idempotency_key' => $idempotencyKey,
                'type' => 'payment',
                'notes' => $data['notes'] ?? null,
            ]);

            // Atomically recompute outstanding balance from invoices minus payments
            $lockedInvoice->recalculateBalance();

            return $payment->load(['invoice.student', 'recorder']);
        });
    }
}
