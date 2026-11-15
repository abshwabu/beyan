<?php

namespace App\Domain\Finance\Services;

use App\Domain\Finance\Models\Expense;
use App\Domain\Finance\Models\FeeInvoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\Institution\Models\Institution;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class FinancialReportingService
{
    /**
     * Compute comprehensive Income vs Expense report across a specified timeframe.
     *
     * @return array<string, mixed>
     */
    public function getIncomeExpenseReport(
        Institution $institution,
        string $startDate,
        string $endDate
    ): array {
        // 1. Incomes: Payments ledger
        $payments = Payment::query()
            ->where('institution_id', $institution->id)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->with(['invoice.feePlan'])
            ->get();

        $grossIncome = (int) $payments->where('type', 'payment')->sum('amount');
        $refundsTotal = (int) $payments->whereIn('type', ['refund', 'reversal'])->sum('amount');
        $netIncome = max(0, $grossIncome - $refundsTotal);

        // Group by payment method
        $incomeByMethod = [];
        foreach ($payments->where('type', 'payment') as $payment) {
            $method = $payment->payment_method ?: 'cash';
            $incomeByMethod[$method] = ($incomeByMethod[$method] ?? 0) + $payment->amount;
        }

        // Group by fee plan
        $incomeByPlan = [];
        foreach ($payments->where('type', 'payment') as $payment) {
            $planName = $payment->invoice?->feePlan?->name ?? 'General Tuition';
            $incomeByPlan[$planName] = ($incomeByPlan[$planName] ?? 0) + $payment->amount;
        }

        // 2. Expenses: Expenses ledger
        $expenses = Expense::query()
            ->where('institution_id', $institution->id)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->get();

        $totalExpenses = (int) $expenses->sum('amount');

        // Group by category
        $expensesByCategory = [
            'salaries' => (int) $expenses->where('category', 'salaries')->sum('amount'),
            'rent' => (int) $expenses->where('category', 'rent')->sum('amount'),
            'utilities' => (int) $expenses->where('category', 'utilities')->sum('amount'),
            'maintenance' => (int) $expenses->where('category', 'maintenance')->sum('amount'),
            'supplies' => (int) $expenses->where('category', 'supplies')->sum('amount'),
            'other' => (int) $expenses->where('category', 'other')->sum('amount'),
        ];

        // 3. Net Financial Position
        $netSurplusDeficit = $netIncome - $totalExpenses;

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'currency' => $institution->currency ?: 'ETB',
            'income' => [
                'gross_amount' => $grossIncome,
                'refunds_amount' => $refundsTotal,
                'net_amount' => $netIncome,
                'by_method' => $incomeByMethod,
                'by_plan' => $incomeByPlan,
            ],
            'expenses' => [
                'total_amount' => $totalExpenses,
                'by_category' => $expensesByCategory,
            ],
            'net_surplus_deficit' => $netSurplusDeficit,
            'is_surplus' => $netSurplusDeficit >= 0,
        ];
    }

    /**
     * Get list of overdue invoices with days overdue.
     *
     * @return Collection<int, FeeInvoice>
     */
    public function getOverdueInvoices(Institution $institution): Collection
    {
        $today = Carbon::today()->toDateString();

        return FeeInvoice::query()
            ->where('institution_id', $institution->id)
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereDate('due_date', '<', $today)
            ->with(['student', 'feePlan'])
            ->orderBy('due_date')
            ->get()
            ->each(function (FeeInvoice $invoice) {
                $invoice->days_overdue = $invoice->due_date ? Carbon::today()->diffInDays($invoice->due_date) : 0;
            });
    }
}
