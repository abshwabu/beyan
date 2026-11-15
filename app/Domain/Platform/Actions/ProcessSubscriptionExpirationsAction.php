<?php

namespace App\Domain\Platform\Actions;

use App\Domain\Institution\Models\Institution;
use App\Domain\Platform\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessSubscriptionExpirationsAction
{
    /**
     * Process subscription expirations, grace periods, and expiration reminders.
     *
     * @return array{expired: int, grace_period: int, reminders_sent: int}
     */
    public function execute(): array
    {
        $now = Carbon::now();
        $expiredCount = 0;
        $graceCount = 0;
        $remindersCount = 0;

        // 1. Process active and grace_period subscriptions
        $subscriptions = Subscription::with('institution')
            ->whereIn('status', ['trial', 'active', 'grace_period'])
            ->whereNotNull('ends_at')
            ->get();

        foreach ($subscriptions as $subscription) {
            $endsAt = $subscription->ends_at;
            $graceDays = $subscription->grace_days ?? 7;
            $expirationThreshold = $endsAt->copy()->addDays($graceDays);

            /** @var Institution|null $institution */
            $institution = $subscription->institution;

            // Scenario A: Subscription is beyond grace period -> Expire & Suspend Institution
            if ($now->greaterThanOrEqualTo($expirationThreshold)) {
                DB::transaction(function () use ($subscription, $institution) {
                    $subscription->update(['status' => 'expired']);

                    if ($institution && $institution->status !== 'suspended') {
                        $oldStatus = $institution->status;
                        $institution->update(['status' => 'suspended']);
                        $institution->recordAudit('subscription_expired_suspended', ['status' => $oldStatus], ['status' => 'suspended']);
                    }

                    $subscription->recordAudit('status_changed', ['status' => $subscription->getOriginal('status')], ['status' => 'expired']);
                });

                $expiredCount++;

                continue;
            }

            // Scenario B: Subscription is past ends_at but still within grace period -> Grace Period
            if ($now->greaterThanOrEqualTo($endsAt) && $subscription->status !== 'grace_period') {
                DB::transaction(function () use ($subscription) {
                    $oldStatus = $subscription->status;
                    $subscription->update(['status' => 'grace_period']);
                    $subscription->recordAudit('entered_grace_period', ['status' => $oldStatus], ['status' => 'grace_period']);
                });

                $graceCount++;
            }

            // Scenario C: Expiry Reminders (7, 3, and 1 days before expiry)
            if ($now->lessThan($endsAt)) {
                $daysRemaining = (int) $now->diffInDays($endsAt, false);
                $history = $subscription->reminder_history ?? [];

                $reminderKey = match ($daysRemaining) {
                    7 => '7d',
                    3 => '3d',
                    1, 0 => '1d',
                    default => null,
                };

                if ($reminderKey && empty($history[$reminderKey])) {
                    // Queue reminder notification outside the transaction
                    $history[$reminderKey] = $now->toIso8601String();
                    $subscription->update(['reminder_history' => $history]);

                    Log::info("Queued {$reminderKey} subscription expiry reminder for institution: {$institution?->name}");
                    $remindersCount++;
                }
            }
        }

        return [
            'expired' => $expiredCount,
            'grace_period' => $graceCount,
            'reminders_sent' => $remindersCount,
        ];
    }
}
