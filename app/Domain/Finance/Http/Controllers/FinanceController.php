<?php

namespace App\Domain\Finance\Http\Controllers;

use App\Domain\Finance\Actions\GenerateInvoiceAction;
use App\Domain\Finance\Actions\RecordPaymentAction;
use App\Domain\Finance\Actions\RecordPaymentReversalAction;
use App\Domain\Finance\Actions\SendPaymentReminderAction;
use App\Domain\Finance\Models\Expense;
use App\Domain\Finance\Models\FeeInvoice;
use App\Domain\Finance\Models\FeePlan;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Services\FinancialReportingService;
use App\Domain\Institution\Models\Institution;
use App\Domain\Students\Models\Student;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    public function __construct(
        protected GenerateInvoiceAction $generateInvoiceAction,
        protected RecordPaymentAction $recordPaymentAction,
        protected RecordPaymentReversalAction $reversalAction,
        protected FinancialReportingService $reportingService,
        protected SendPaymentReminderAction $reminderAction
    ) {}

    /**
     * Display a listing of invoices.
     */
    public function index(Request $request): Response|JsonResponse
    {
        Gate::authorize('viewAny', FeeInvoice::class);

        $query = FeeInvoice::query()
            ->with(['student', 'feePlan'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        if ($request->filled('period')) {
            $query->where('period', $request->input('period'));
        }

        if ($request->wantsJson()) {
            return response()->json($query->get());
        }

        $invoices = $query->paginate(20)->withQueryString();

        return Inertia::render('Finance/Invoices/Index', [
            'invoices' => $invoices,
            'filters' => $request->only(['status', 'student_id', 'period']),
        ]);
    }

    /**
     * Display the specified invoice with items and payment history.
     */
    public function show(Request $request, string $id): Response|JsonResponse
    {
        $invoice = FeeInvoice::with(['student.guardians', 'feePlan', 'items', 'payments.recorder', 'payments.reversals'])
            ->findOrFail($id);

        Gate::authorize('view', $invoice);

        if ($request->wantsJson()) {
            return response()->json($invoice);
        }

        return Inertia::render('Finance/Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Store or generate a new invoice.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        Gate::authorize('create', FeeInvoice::class);

        // If fee_plan_id and period are present, route through GenerateInvoiceAction
        if ($request->filled('fee_plan_id') && $request->filled('period')) {
            $validated = $request->validate([
                'student_id' => ['required', 'string'],
                'fee_plan_id' => ['required', 'string'],
                'period' => ['required', 'string'],
                'due_date' => ['nullable', 'date'],
                'title' => ['nullable', 'string', 'max:255'],
                'notes' => ['nullable', 'string'],
            ]);

            $invoice = $this->generateInvoiceAction->execute($validated);

            if ($request->wantsJson()) {
                return response()->json($invoice, 201);
            }

            return redirect()->route('finance.invoices.show', $invoice->id)
                ->with('success', "Invoice {$invoice->invoice_number} generated successfully.");
        }

        // Standard / manual invoice creation fallback
        $validated = $request->validate([
            'student_id' => ['required', 'string'],
            'invoice_number' => ['required', 'string'],
            'title' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $totalMinorUnits = (int) round(((float) $validated['amount']) * 100);

        $invoice = FeeInvoice::create([
            'student_id' => $validated['student_id'],
            'invoice_number' => $validated['invoice_number'],
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'subtotal' => $totalMinorUnits,
            'discount_total' => 0,
            'total_amount' => $totalMinorUnits,
            'paid_amount' => 0,
            'balance_due' => $totalMinorUnits,
            'status' => 'unpaid',
            'due_date' => $validated['due_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json($invoice, 201);
        }

        return redirect()->route('finance.invoices.show', $invoice->id)
            ->with('success', 'Invoice created successfully.');
    }

    /**
     * Record a payment against an invoice (idempotent, append-only, balance recomputed atomically).
     */
    public function recordPayment(Request $request, string $invoiceId): JsonResponse|RedirectResponse
    {
        Gate::authorize('recordPayment', FeeInvoice::class);

        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'amount_minor_units' => ['nullable', 'integer', 'min:1'],
            'payment_method' => ['required', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'date' => ['nullable', 'date'],
            'idempotency_key' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['fee_invoice_id'] = $invoiceId;
        $validated['recorded_by'] = $request->user()->id;

        $payment = $this->recordPaymentAction->execute($validated);
        $status = $payment->wasRecentlyCreated ? 201 : 200;

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Payment recorded successfully.',
                'payment' => $payment,
                'balance_due' => $payment->invoice->balance_due,
                'status' => $payment->invoice->status,
            ], $status);
        }

        return redirect()->back()->with('success', "Payment {$payment->payment_number} recorded successfully.");
    }

    /**
     * Record a payment reversal or refund (append-only correction).
     */
    public function recordReversal(Request $request, string $paymentId): JsonResponse|RedirectResponse
    {
        Gate::authorize('recordPayment', FeeInvoice::class);

        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'amount_minor_units' => ['nullable', 'integer', 'min:1'],
            'type' => ['nullable', 'string', 'in:refund,reversal'],
            'reason' => ['required', 'string', 'max:1000'],
            'idempotency_key' => ['required', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'date' => ['nullable', 'date'],
        ]);

        $validated['original_payment_id'] = $paymentId;
        $validated['recorded_by'] = $request->user()->id;

        $reversal = $this->reversalAction->execute($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Payment reversal/refund recorded successfully.',
                'reversal' => $reversal,
                'balance_due' => $reversal->invoice->balance_due,
                'status' => $reversal->invoice->status,
            ], 201);
        }

        return redirect()->back()->with('success', 'Payment reversal recorded successfully.');
    }

    /**
     * Printable PDF / HTML receipt view for a payment.
     */
    public function receipt(Request $request, string $paymentId): HttpResponse|JsonResponse
    {
        $payment = Payment::with(['invoice.student', 'recorder'])->findOrFail($paymentId);

        Gate::authorize('view', $payment->invoice);

        /** @var Institution $institution */
        $institution = $request->user()->institution;

        if ($request->wantsJson()) {
            return response()->json([
                'payment' => $payment,
                'invoice' => $payment->invoice,
                'student' => $payment->invoice->student,
                'institution' => $institution,
            ]);
        }

        return response()->view('finance.receipt', [
            'payment' => $payment,
            'invoice' => $payment->invoice,
            'student' => $payment->invoice->student,
            'institution' => $institution,
        ]);
    }

    /**
     * List and create fee plans.
     */
    public function feePlans(Request $request): JsonResponse|Response
    {
        Gate::authorize('viewAny', FeeInvoice::class);

        $plans = FeePlan::with('course')->orderBy('name')->get();

        if ($request->wantsJson()) {
            return response()->json($plans);
        }

        return Inertia::render('Finance/FeePlans/Index', [
            'plans' => $plans,
        ]);
    }

    /**
     * Store a new fee plan.
     */
    public function storeFeePlan(Request $request): JsonResponse|RedirectResponse
    {
        Gate::authorize('create', FeeInvoice::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'course_id' => ['nullable', 'string'],
            'period_type' => ['required', 'string', 'in:monthly,term,one-time'],
            'amount' => ['required', 'integer', 'min:1'], // minor units
            'currency' => ['nullable', 'string', 'size:3'],
            'description' => ['nullable', 'string'],
        ]);

        $plan = FeePlan::create($validated);

        if ($request->wantsJson()) {
            return response()->json($plan, 201);
        }

        return redirect()->back()->with('success', 'Fee plan created successfully.');
    }

    /**
     * Store student discount or scholarship.
     */
    public function storeStudentDiscount(Request $request, Student $student): JsonResponse|RedirectResponse
    {
        Gate::authorize('create', FeeInvoice::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'fee_plan_id' => ['nullable', 'string'],
            'discount_type' => ['required', 'string', 'in:percentage,fixed_amount'],
            'discount_value' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $discount = $student->discounts()->create([
            'institution_id' => $student->institution_id,
            ...$validated,
        ]);

        if ($request->wantsJson()) {
            return response()->json($discount, 201);
        }

        return redirect()->back()->with('success', 'Discount / Scholarship applied to student.');
    }

    /**
     * List and record expenses.
     */
    public function expenses(Request $request): JsonResponse|Response
    {
        Gate::authorize('viewAny', FeeInvoice::class);

        $query = Expense::query()->with('recorder')->orderByDesc('date');

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->input('start_date'), $request->input('end_date')]);
        }

        if ($request->wantsJson()) {
            return response()->json($query->get());
        }

        $expenses = $query->paginate(20)->withQueryString();

        return Inertia::render('Finance/Expenses/Index', [
            'expenses' => $expenses,
        ]);
    }

    /**
     * Store an expense.
     */
    public function storeExpense(Request $request): JsonResponse|RedirectResponse
    {
        Gate::authorize('create', FeeInvoice::class);

        $validated = $request->validate([
            'category' => ['required', 'string', 'in:salaries,rent,utilities,maintenance,supplies,other'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1'], // minor units
            'date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string'],
            'reference_number' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['recorded_by'] = $request->user()->id;

        $expense = Expense::create($validated);

        if ($request->wantsJson()) {
            return response()->json($expense, 201);
        }

        return redirect()->back()->with('success', 'Expense recorded successfully.');
    }

    /**
     * Income vs Expense financial report.
     */
    public function incomeExpenseReport(Request $request): JsonResponse|Response
    {
        Gate::authorize('viewReports', FeeInvoice::class);

        /** @var Institution $institution */
        $institution = $request->user()->institution;

        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $report = $this->reportingService->getIncomeExpenseReport($institution, $startDate, $endDate);

        if ($request->wantsJson()) {
            return response()->json($report);
        }

        return Inertia::render('Finance/Reports/IncomeExpense', [
            'report' => $report,
        ]);
    }

    /**
     * List overdue invoices for payment reminder follow-up.
     */
    public function overdueReminders(Request $request): JsonResponse|Response
    {
        Gate::authorize('viewAny', FeeInvoice::class);

        /** @var Institution $institution */
        $institution = $request->user()->institution;

        $overdueInvoices = $this->reportingService->getOverdueInvoices($institution);

        if ($request->wantsJson()) {
            return response()->json($overdueInvoices);
        }

        return Inertia::render('Finance/Reminders/Index', [
            'overdueInvoices' => $overdueInvoices,
        ]);
    }

    /**
     * Send payment reminder notification stub.
     */
    public function sendReminder(Request $request, string $invoiceId): JsonResponse|RedirectResponse
    {
        Gate::authorize('create', FeeInvoice::class);

        $invoice = FeeInvoice::with(['student.guardians', 'institution'])->findOrFail($invoiceId);
        $channel = $request->input('channel', 'sms');

        $result = $this->reminderAction->execute($invoice, $channel);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Payment reminder dispatched successfully.',
                'result' => $result,
            ]);
        }

        return redirect()->back()->with('success', "Payment reminder dispatched via {$channel}.");
    }
}
