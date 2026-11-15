<?php

namespace App\Domain\Platform\Actions;

use App\Domain\Finance\Models\Plan;
use App\Domain\Institution\Models\Institution;
use App\Domain\Platform\Models\PlatformPayment;
use App\Domain\Platform\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecordPlatformPaymentAction
{
    /**
     * Record a manual subscription payment for an institution.
     * Enforces strict idempotency and automatically extends subscription duration.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, string $recordedBy): PlatformPayment
    {
        // 1. Idempotency Check: Return existing payment if idempotency key was already processed
        $idempotencyKey = $data['idempotency_key'];
        $existing = PlatformPayment::where('idempotency_key', $idempotencyKey)->first();

        if ($existing) {
            Log::info("Idempotent payment request detected for key: {$idempotencyKey}. Returning existing record.");

            return $existing;
        }

        /** @var Institution $institution */
        $institution = Institution::findOrFail($data['institution_id']);

        // 2. Short database transaction
        return DB::transaction(function () use ($data, $recordedBy, $institution, $idempotencyKey) {
            // Find or create subscription
            /** @var Subscription|null $subscription */
            $subscription = null;

            if (! empty($data['subscription_id'])) {
                $subscription = Subscription::find($data['subscription_id']);
            }

            if (! $subscription) {
                $subscription = $institution->latestSubscription;
            }

            if (! $subscription) {
                // Create subscription record matching institution's plan
                $plan = Plan::where('slug', $institution->subscription_plan)->first();

                $subscription = Subscription::create([
                    'institution_id' => $institution->id,
                    'plan_id' => $plan?->id,
                    'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                    'status' => 'active',
                    'starts_at' => Carbon::now(),
                    'ends_at' => Carbon::now(),
                    'grace_days' => 7,
                ]);
            }

            // Create append-only payment record
            $payment = PlatformPayment::create([
                'institution_id' => $institution->id,
                'subscription_id' => $subscription->id,
                'amount' => (int) $data['amount'],
                'currency' => $data['currency'] ?? 'ETB',
                'method' => $data['method'] ?? 'bank_transfer',
                'reference' => $data['reference'] ?? null,
                'paid_at' => ! empty($data['paid_at']) ? Carbon::parse($data['paid_at']) : Carbon::now(),
                'recorded_by' => $recordedBy,
                'note' => $data['note'] ?? null,
                'proof_path' => $data['proof_path'] ?? null,
                'status' => 'completed',
                'idempotency_key' => $idempotencyKey,
            ]);

            // Calculate extension period
            $billingCycle = $data['billing_cycle'] ?? $subscription->billing_cycle ?? 'monthly';
            $daysToAdd = ($billingCycle === 'yearly') ? 365 : 30;

            $baseDate = ($subscription->ends_at && $subscription->ends_at->isFuture())
                ? $subscription->ends_at
                : Carbon::now();

            $newEndsAt = $baseDate->copy()->addDays($daysToAdd);

            // Update subscription
            $subscription->update([
                'status' => 'active',
                'ends_at' => $newEndsAt,
                'billing_cycle' => $billingCycle,
            ]);

            // Sync with institution record
            $oldStatus = $institution->status;
            $institution->update([
                'subscription_ends_at' => $newEndsAt,
                'status' => ($institution->status === 'suspended') ? 'active' : $institution->status,
            ]);

            // Audit logging inside transaction
            $payment->recordAudit('payment_recorded', null, [
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'method' => $payment->method,
                'reference' => $payment->reference,
                'new_ends_at' => $newEndsAt->toIso8601String(),
            ]);

            if ($oldStatus === 'suspended') {
                $institution->recordAudit('reactivated_via_payment', ['status' => 'suspended'], ['status' => 'active']);
            }

            return $payment;
        });
    }
}
