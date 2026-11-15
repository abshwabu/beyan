<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Models\FeeInvoice;
use Illuminate\Support\Facades\Log;

class SendPaymentReminderAction
{
    /**
     * Dispatch an overdue payment reminder notification (stub ready for SMS / Email integrations).
     *
     * @return array<string, mixed>
     */
    public function execute(FeeInvoice $invoice, string $channel = 'sms'): array
    {
        $student = $invoice->student;
        $primaryGuardian = $student?->primaryGuardian();
        $recipientPhone = $primaryGuardian?->phone ?: $student?->phone;
        $recipientEmail = $primaryGuardian?->email ?: $student?->email;

        // Update reminder timestamp on invoice
        $invoice->update([
            'last_reminder_sent_at' => now(),
        ]);

        $currency = $invoice->institution?->currency ?: 'ETB';
        $formattedBalance = number_format($invoice->balance_due / 100, 2).' '.$currency;

        $message = "Assalamu Alaikum. Reminder: An outstanding balance of {$formattedBalance} for {$student?->full_name} ({$invoice->title}) was due on {$invoice->due_date?->toDateString()}. Please complete payment at your earliest convenience.";

        Log::info("Payment Reminder Sent [{$channel}]: Invoice {$invoice->invoice_number} to {$recipientPhone}/{$recipientEmail}");

        return [
            'status' => 'queued_or_sent',
            'channel' => $channel,
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'student_name' => $student?->full_name,
            'recipient_phone' => $recipientPhone,
            'recipient_email' => $recipientEmail,
            'balance_due' => $invoice->balance_due,
            'formatted_balance' => $formattedBalance,
            'message' => $message,
            'dispatched_at' => now()->toIso8601String(),
        ];
    }
}
