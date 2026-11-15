<?php

namespace App\Domain\Finance\Models;

use App\Domain\Auth\Models\User;
use App\Domain\Institution\Audit\Auditable;
use App\Domain\Institution\Tenancy\BelongsToInstitution;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

class Payment extends Model
{
    use Auditable, BelongsToInstitution, HasFactory, HasUlids;

    protected $table = 'payments';

    protected $fillable = [
        'institution_id',
        'fee_invoice_id',
        'payment_number',
        'amount', // integer minor units
        'payment_method', // cash|bank_transfer|other
        'reference_number',
        'date',
        'recorded_by',
        'idempotency_key',
        'type', // payment|refund|reversal
        'reversal_payment_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        // Enforce append-only invariant: payments cannot be edited or deleted
        static::updating(function (Payment $payment) {
            throw new RuntimeException('Payments are append-only and cannot be modified. Issue a refund or reversal record instead.');
        });

        static::deleting(function (Payment $payment) {
            throw new RuntimeException('Payments are append-only and cannot be deleted. Issue a refund or reversal record instead.');
        });
    }

    protected static function newFactory(): PaymentFactory
    {
        return PaymentFactory::new();
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FeeInvoice::class, 'fee_invoice_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function originalPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'reversal_payment_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(Payment::class, 'reversal_payment_id');
    }

    public function isReversal(): bool
    {
        return in_array($this->type, ['refund', 'reversal'], true);
    }
}
