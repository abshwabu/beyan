<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Models\FeeInvoice;
use App\Domain\Finance\Models\FeePlan;
use App\Domain\Finance\Models\StudentDiscount;
use App\Domain\Finance\Services\FinanceNumberGenerator;
use App\Domain\Institution\Models\Institution;
use App\Domain\Students\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GenerateInvoiceAction
{
    public function __construct(
        protected FinanceNumberGenerator $numberGenerator
    ) {}

    /**
     * Generate an invoice for a student and fee plan for a specific period.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function execute(array $data): FeeInvoice
    {
        $studentId = $data['student_id'];
        $feePlanId = $data['fee_plan_id'];
        $period = trim((string) $data['period']);

        /** @var Student $student */
        $student = Student::query()->findOrFail($studentId);

        /** @var FeePlan $feePlan */
        $feePlan = FeePlan::query()->findOrFail($feePlanId);

        // Check for duplicates at application level before transaction
        $exists = FeeInvoice::query()
            ->where('student_id', $student->id)
            ->where('fee_plan_id', $feePlan->id)
            ->where('period', $period)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'period' => ["An invoice already exists for student {$student->full_name}, plan '{$feePlan->name}', for period '{$period}'."],
            ]);
        }

        // Calculate discounts applied to this student
        $discounts = StudentDiscount::query()
            ->where('student_id', $student->id)
            ->where('is_active', true)
            ->where(function ($q) use ($feePlan) {
                $q->whereNull('fee_plan_id')
                    ->orWhere('fee_plan_id', $feePlan->id);
            })
            ->get();

        $subtotal = (int) $feePlan->amount;
        $discountTotal = 0;

        foreach ($discounts as $discount) {
            $discountAmount = $discount->computeDiscount($subtotal - $discountTotal);
            $discountTotal += $discountAmount;
        }

        $totalAmount = max(0, $subtotal - $discountTotal);
        $dueDate = ! empty($data['due_date'])
            ? Carbon::parse($data['due_date'])->toDateString()
            : now()->addDays(15)->toDateString();

        $title = ! empty($data['title'])
            ? (string) $data['title']
            : "{$feePlan->name} - {$period}";

        /** @var Institution $institution */
        $institution = $student->institution ?? Institution::findOrFail($student->institution_id);

        return DB::transaction(function () use (
            $institution,
            $student,
            $feePlan,
            $period,
            $title,
            $subtotal,
            $discountTotal,
            $totalAmount,
            $dueDate,
            $data
        ) {
            $invoiceNumber = $this->numberGenerator->nextInvoiceNumber($institution);

            /** @var FeeInvoice $invoice */
            $invoice = FeeInvoice::create([
                'institution_id' => $institution->id,
                'student_id' => $student->id,
                'fee_plan_id' => $feePlan->id,
                'invoice_number' => $invoiceNumber,
                'title' => $title,
                'period' => $period,
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'total_amount' => $totalAmount,
                'amount' => $totalAmount / 100,
                'paid_amount' => 0,
                'balance_due' => $totalAmount,
                'status' => $totalAmount === 0 ? 'paid' : 'unpaid',
                'due_date' => $dueDate,
                'notes' => $data['notes'] ?? null,
            ]);

            $invoice->items()->create([
                'institution_id' => $institution->id,
                'fee_plan_id' => $feePlan->id,
                'description' => $title,
                'amount' => $subtotal,
                'discount_amount' => $discountTotal,
                'final_amount' => $totalAmount,
            ]);

            return $invoice->load(['items', 'student', 'feePlan']);
        });
    }
}
