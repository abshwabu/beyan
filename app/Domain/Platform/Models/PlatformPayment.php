<?php

namespace App\Domain\Platform\Models;

use App\Domain\Institution\Audit\Auditable;
use App\Domain\Institution\Models\Institution;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformPayment extends Model
{
    use Auditable, HasFactory, HasUlids;

    protected $table = 'platform_payments';

    protected $fillable = [
        'institution_id',
        'subscription_id',
        'amount',
        'currency',
        'method',
        'reference',
        'paid_at',
        'recorded_by',
        'note',
        'proof_path',
        'status',
        'reversal_reason',
        'reversed_at',
        'reversed_by',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isReversed(): bool
    {
        return $this->status === 'reversed';
    }

    /**
     * Amount in major units (e.g. 1400.00).
     */
    public function getAmountMajorAttribute(): float
    {
        return $this->amount / 100;
    }
}
