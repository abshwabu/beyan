<?php

namespace App\Domain\Platform\Http\Controllers;

use App\Domain\Institution\Models\Institution;
use App\Domain\Platform\Actions\RecordPlatformPaymentAction;
use App\Domain\Platform\Actions\ReversePlatformPaymentAction;
use App\Domain\Platform\Models\PlatformPayment;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlatformPaymentController extends Controller
{
    /**
     * Display list of all subscription payments.
     */
    public function index(Request $request): Response
    {
        $query = PlatformPayment::with(['institution', 'subscription.plan']);

        if ($method = $request->query('method')) {
            $query->where('method', $method);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($institutionId = $request->query('institution_id')) {
            $query->where('institution_id', $institutionId);
        }

        if ($from = $request->query('date_from')) {
            $query->whereDate('paid_at', '>=', $from);
        }

        if ($to = $request->query('date_to')) {
            $query->whereDate('paid_at', '<=', $to);
        }

        $payments = $query->latest('paid_at')->paginate(20)->withQueryString();
        $institutions = Institution::select('id', 'name')->orderBy('name')->get();

        $totalCompleted = PlatformPayment::where('status', 'completed')->sum('amount') / 100;

        return Inertia::render('Platform/Payments/Index', [
            'payments' => $payments,
            'filters' => $request->only(['method', 'status', 'institution_id', 'date_from', 'date_to']),
            'institutions' => $institutions,
            'totalCompleted' => $totalCompleted,
        ]);
    }

    /**
     * Record a manual payment for an institution subscription.
     */
    public function store(Request $request, RecordPlatformPaymentAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'institution_id' => ['required', 'exists:institutions,id'],
            'amount_major' => ['required', 'numeric', 'min:1'],
            'currency' => ['required', 'string', 'max:10'],
            'method' => ['required', 'in:cash,bank_transfer,mobile_money,other'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'reference' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
        ]);

        $idempotencyKey = $validated['idempotency_key'] ?: (string) Str::uuid();

        $payload = [
            'institution_id' => $validated['institution_id'],
            'amount' => (int) round($validated['amount_major'] * 100),
            'currency' => $validated['currency'],
            'method' => $validated['method'],
            'billing_cycle' => $validated['billing_cycle'],
            'reference' => $validated['reference'] ?? null,
            'paid_at' => $validated['paid_at'] ?? null,
            'note' => $validated['note'] ?? null,
            'idempotency_key' => $idempotencyKey,
        ];

        $payment = $action->execute($payload, $request->user()->name ?? 'Platform Administrator');

        return redirect()->route('platform.payments.index')
            ->with('success', "Payment of {$payment->amount_major} {$payment->currency} recorded. Subscription extended.");
    }

    /**
     * Reverse a recorded payment with reason.
     */
    public function reverse(Request $request, string $id, ReversePlatformPaymentAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $action->execute($id, $validated['reason'], $request->user()->name ?? 'Platform Administrator');

        return back()->with('success', 'Payment reversed successfully.');
    }

    /**
     * Printable PDF/HTML Receipt for institution subscription payment.
     */
    public function receipt(string $id): HttpResponse
    {
        $payment = PlatformPayment::with(['institution', 'subscription.plan'])->findOrFail($id);

        $html = view('platform.receipt', [
            'payment' => $payment,
            'institution' => $payment->institution,
            'subscription' => $payment->subscription,
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    /**
     * Export revenue report to CSV with UTF-8 BOM.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = PlatformPayment::with('institution')->latest('paid_at');

        if ($from = $request->query('date_from')) {
            $query->whereDate('paid_at', '>=', $from);
        }

        if ($to = $request->query('date_to')) {
            $query->whereDate('paid_at', '<=', $to);
        }

        $payments = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="platform-revenue-report-'.date('Y-m-d').'.csv"',
        ];

        return response()->stream(function () use ($payments) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Payment ID',
                'Institution Name',
                'Amount (ETB)',
                'Currency',
                'Method',
                'Reference',
                'Date',
                'Status',
                'Recorded By',
                'Reversal Reason',
            ]);

            foreach ($payments as $p) {
                fputcsv($handle, [
                    $p->id,
                    $p->institution?->name ?? 'N/A',
                    $p->amount_major,
                    $p->currency,
                    $p->method,
                    $p->reference ?? '',
                    $p->paid_at?->format('Y-m-d H:i') ?? '',
                    $p->status,
                    $p->recorded_by,
                    $p->reversal_reason ?? '',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
