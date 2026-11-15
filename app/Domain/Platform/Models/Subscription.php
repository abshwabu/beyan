<?php

namespace App\Domain\Platform\Models;

use App\Domain\Finance\Models\Plan;
use App\Domain\Institution\Audit\Auditable;
use App\Domain\Institution\Models\Institution;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use Auditable, HasFactory, HasUlids, SoftDeletes;

    protected $table = 'subscriptions';

    protected $fillable = [
        'institution_id',
        'plan_id',
        'billing_cycle',
        'status',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'grace_days',
        'reminder_history',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'grace_days' => 'integer',
            'reminder_history' => 'array',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PlatformPayment::class, 'subscription_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial';
    }

    public function isGracePeriod(): bool
    {
        return $this->status === 'grace_period';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    /**
     * Compute remaining days until expiry.
     */
    public function daysRemaining(): int
    {
        $target = $this->ends_at ?? $this->trial_ends_at;

        if (! $target) {
            return 0;
        }

        return (int) Carbon::now()->diffInDays($target, false);
    }

    /**
     * Determine if subscription is overdue or within grace period.
     */
    public function isOverdue(): bool
    {
        $target = $this->ends_at ?? $this->trial_ends_at;

        if (! $target) {
            return false;
        }

        return Carbon::now()->isAfter($target);
    }
}
