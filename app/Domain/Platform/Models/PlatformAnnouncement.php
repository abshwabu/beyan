<?php

namespace App\Domain\Platform\Models;

use App\Domain\Institution\Audit\Auditable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformAnnouncement extends Model
{
    use Auditable, HasFactory, HasUlids;

    protected $table = 'platform_announcements';

    protected $fillable = [
        'title',
        'body',
        'target_mode',
        'target_plan',
        'starts_at',
        'ends_at',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function dismissals(): HasMany
    {
        return $this->hasMany(PlatformAnnouncementDismissal::class, 'announcement_id');
    }

    /**
     * Scope announcements that are currently active in time.
     */
    public function scopeActive(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query->where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now);
    }

    /**
     * Check if this announcement targets a given institution's mode and plan.
     */
    public function matchesTarget(?string $mode = null, ?string $plan = null): bool
    {
        if ($this->target_mode && $this->target_mode !== 'all' && $this->target_mode !== $mode) {
            return false;
        }

        if ($this->target_plan && $this->target_plan !== 'all' && $this->target_plan !== $plan) {
            return false;
        }

        return true;
    }
}
