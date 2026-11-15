<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #{{ $payment->reference ?: $payment->id }} - Beyan Platform</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 40px; color: #1e293b; }
        .receipt-card { max-width: 650px; margin: 0 auto; border: 1px solid #cbd5e1; border-radius: 12px; padding: 32px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; }
        .logo { font-size: 24px; font-weight: 800; color: #047857; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .badge-success { background: #dcfce7; color: #166534; }
        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 24px 0; font-size: 14px; }
        .label { color: #64748b; font-size: 12px; text-transform: uppercase; font-weight: 600; margin-bottom: 4px; }
        .value { font-weight: 600; }
        .amount-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; text-align: center; margin: 24px 0; }
        .amount { font-size: 32px; font-weight: 900; color: #0f172a; }
        .footer { text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 16px; }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="max-width: 650px; margin: 0 auto 16px auto; text-align: right;">
        <button onclick="window.print()" style="background: #047857; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer;">
            🖨️ Print Receipt / Save as PDF
        </button>
    </div>

    <div class="receipt-card">
        <div class="header">
            <div>
                <div class="logo">Beyan Madrasa SaaS</div>
                <div style="font-size: 12px; color: #64748b;">Subscription Payment Receipt</div>
            </div>
            <div>
                <span class="badge badge-success">{{ strtoupper($payment->status) }}</span>
            </div>
        </div>

        <div class="details-grid">
            <div>
                <div class="label">Receipt Reference</div>
                <div class="value font-mono">{{ $payment->reference ?: substr($payment->id, 0, 12) }}</div>
            </div>
            <div>
                <div class="label">Payment Date</div>
                <div class="value">{{ $payment->paid_at ? $payment->paid_at->format('M d, Y H:i') : now()->format('M d, Y') }}</div>
            </div>
            <div>
                <div class="label">Billed Institution</div>
                <div class="value">{{ $institution->name }}</div>
            </div>
            <div>
                <div class="label">Payment Method</div>
                <div class="value" style="text-transform: capitalize;">{{ str_replace('_', ' ', $payment->method) }}</div>
            </div>
        </div>

        <div class="amount-box">
            <div class="label">Total Paid</div>
            <div class="amount">{{ number_format($payment->amount_major, 2) }} {{ $payment->currency }}</div>
            <div style="font-size: 13px; color: #64748b; margin-top: 6px;">
                Plan: {{ $subscription?->plan?->name ?? 'Standard Plan' }} ({{ ucfirst($subscription?->billing_cycle ?? 'Monthly') }})
            </div>
        </div>

        <div class="details-grid">
            <div>
                <div class="label">Recorded By</div>
                <div class="value">{{ $payment->recorded_by }}</div>
            </div>
            <div>
                <div class="label">New Subscription End</div>
                <div class="value">{{ $subscription?->ends_at ? $subscription->ends_at->format('M d, Y') : 'Active' }}</div>
            </div>
        </div>

        @if($payment->note)
            <div style="margin: 16px 0; font-size: 13px; background: #fff; padding: 12px; border-left: 3px solid #047857;">
                <strong>Note:</strong> {{ $payment->note }}
            </div>
        @endif

        <div class="footer">
            Official Platform Receipt · Beyan Academy Software · Addis Ababa, Ethiopia · salam@beyan.io
        </div>
    </div>
</body>
</html>
