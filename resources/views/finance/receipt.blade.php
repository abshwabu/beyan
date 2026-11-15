<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $payment->payment_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 30px;
            background-color: #f8fafc;
        }
        .receipt-card {
            max-width: 650px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 36px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #059669;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .logo-title {
            font-size: 24px;
            font-weight: 800;
            color: #065f46;
            margin: 0;
        }
        .institution-sub {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }
        .receipt-badge {
            text-align: right;
        }
        .badge {
            display: inline-block;
            background-color: #d1fae5;
            color: #065f46;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .receipt-number {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 6px;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
            background: #f8fafc;
            padding: 16px;
            border-radius: 12px;
            font-size: 13px;
        }
        .meta-item strong {
            display: block;
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            font-size: 14px;
        }
        th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 700;
            text-align: left;
            padding: 10px 14px;
            border-bottom: 1px solid #cbd5e1;
        }
        td {
            padding: 12px 14px;
            border-bottom: 1px solid #e2e8f0;
        }
        .text-right {
            text-align: right;
        }
        .total-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }
        .totals-table {
            width: 280px;
            font-size: 13px;
        }
        .totals-table tr td:last-child {
            text-align: right;
            font-weight: bold;
        }
        .grand-total {
            background-color: #ecfdf5;
            color: #065f46;
            font-size: 16px;
            font-weight: 900 !important;
        }
        .footer {
            border-top: 1px dashed #cbd5e1;
            padding-top: 16px;
            font-size: 11px;
            color: #94a3b8;
            display: flex;
            justify-content: space-between;
        }
        .actions {
            max-width: 650px;
            margin: 16px auto 0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .btn {
            background-color: #059669;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-outline {
            background-color: white;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .receipt-card {
                border: none;
                box-shadow: none;
                padding: 0;
            }
            .actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button class="btn btn-outline" onclick="window.close()">Close</button>
        <button class="btn" onclick="window.print()">Print / Save PDF</button>
    </div>

    <div class="receipt-card">
        <div class="header">
            <div>
                <h1 class="logo-title">{{ $institution->name }}</h1>
                <div class="institution-sub">
                    {{ $institution->contact['email'] ?? '' }}
                    @if(isset($institution->contact['phone'])) · {{ $institution->contact['phone'] }} @endif
                </div>
            </div>
            <div class="receipt-badge">
                <span class="badge">Official Receipt</span>
                <div class="receipt-number">{{ $payment->payment_number }}</div>
            </div>
        </div>

        <div class="meta-grid">
            <div class="meta-item">
                <strong>Student Name</strong>
                <span>{{ $student->full_name }} ({{ $student->student_code }})</span>
            </div>
            <div class="meta-item">
                <strong>Date & Time</strong>
                <span>{{ $payment->date->toDateString() }} {{ $payment->created_at->format('H:i') }}</span>
            </div>
            <div class="meta-item">
                <strong>Invoice Reference</strong>
                <span>{{ $invoice->invoice_number }} ({{ $invoice->title }})</span>
            </div>
            <div class="meta-item">
                <strong>Payment Method</strong>
                <span style="text-transform: capitalize;">{{ str_replace('_', ' ', $payment->payment_method) }}
                    @if($payment->reference_number) (Ref: {{ $payment->reference_number }}) @endif
                </span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Period</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $invoice->title }}</td>
                    <td>{{ $invoice->period ?? 'Current' }}</td>
                    <td class="text-right">{{ number_format($payment->amount / 100, 2) }} {{ $institution->currency }}</td>
                </tr>
            </tbody>
        </table>

        <div class="total-section">
            <table class="totals-table">
                <tr>
                    <td>Invoice Total:</td>
                    <td>{{ number_format(($invoice->total_amount ?: round($invoice->amount * 100)) / 100, 2) }} {{ $institution->currency }}</td>
                </tr>
                <tr class="grand-total">
                    <td>Amount Paid:</td>
                    <td>{{ number_format($payment->amount / 100, 2) }} {{ $institution->currency }}</td>
                </tr>
                <tr>
                    <td>Remaining Balance:</td>
                    <td style="color: {{ $invoice->balance_due > 0 ? '#b91c1c' : '#047857' }};">
                        {{ number_format($invoice->balance_due / 100, 2) }} {{ $institution->currency }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <span>Recorded By: {{ $payment->recorder?->name ?? 'Accounts Department' }}</span>
            <span>Generated on {{ now()->format('Y-m-d H:i:s') }}</span>
        </div>
    </div>
</body>
</html>
