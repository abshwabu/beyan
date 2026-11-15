<?php

namespace App\Domain\Finance\Models;

use App\Domain\Institution\Audit\Auditable;
use App\Domain\Institution\Tenancy\BelongsToInstitution;
use App\Domain\Students\Models\Student;
use Database\Factories\FeeInvoiceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeInvoice extends Model
{
    use Auditable, BelongsToInstitution, HasFactory, HasUlids;

    protected $table = 'fee_invoices';

    protected $fillable = [
        'institution_id',
        'student_id',
        'fee_plan_id',
        'invoice_number',
        'title',
        'period',
        'subtotal',
        'discount_total',
        'total_amount',
        'amount',
        'paid_amount',
        'balance_due',
        'status', // unpaid|partial|paid|void
        'due_date',
        'notes',
        'last_reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'total_amount' => 'integer',
            'balance_due' => 'integer',
            'due_date' => 'date',
            'last_reminder_sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (FeeInvoice $invoice) {
            // Synchronize legacy decimal amount with minor units total_amount if one is provided
            if (empty($invoice->total_amount) && ! empty($invoice->amount)) {
                $invoice->total_amount = (int) round($invoice->amount * 100);
            } elseif (! empty($invoice->total_amount) && empty($invoice->amount)) {
                $invoice->amount = $invoice->total_amount / 100;
            }

            if (empty($invoice->subtotal) && ! empty($invoice->total_amount)) {
                $invoice->subtotal = $invoice->total_amount;
            }

            if (empty($invoice->status) || $invoice->status === 'pending') {
                $invoice->status = 'unpaid';
            }

            if (! isset($invoice->balance_due) || $invoice->balance_due === 0) {
                $invoice->balance_due = max(0, (int) $invoice->total_amount - (int) ($invoice->paid_amount * 100));
            }
        });
    }

    protected static function newFactory(): FeeInvoiceFactory
    {
        return FeeInvoiceFactory::new();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function feePlan(): BelongsTo
    {
        return $this->belongsTo(FeePlan::class, 'fee_plan_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'fee_invoice_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'fee_invoice_id');
    }

    /**
     * Atomically recalculate paid amount, balance due, and status from the payments ledger.
     */
    public function recalculateBalance(): self
    {
        $allPayments = $this->payments()->get();

        $grossPaid = (int) $allPayments->where('type', 'payment')->sum('amount');
        $refunded = (int) $allPayments->whereIn('type', ['refund', 'reversal'])->sum('amount');
        $netPaid = max(0, $grossPaid - $refunded);

        $total = $this->total_amount ?: (int) round($this->amount * 100);
        $balance = max(0, $total - $netPaid);

        $newStatus = match (true) {
            $this->status === 'void' => 'void',
            $netPaid <= 0 => 'unpaid',
            $netPaid >= $total => 'paid',
            default => 'partial',
        };

        $this->update([
            'paid_amount' => $netPaid / 100, // keep legacy field in sync
            'balance_due' => $balance,
            'status' => $newStatus,
        ]);

        return $this;
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue(): bool
    {
        return in_array($this->status, ['unpaid', 'partial'], true)
            && $this->due_date
            && $this->due_date->isPast();
    }
}
