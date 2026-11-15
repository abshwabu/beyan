<?php

namespace App\Domain\Institution\Audit\Models;

use App\Domain\Auth\Models\User;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\BelongsToInstitution;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use BelongsToInstitution, HasUlids;

    protected $table = 'audit_logs';

    protected $fillable = [
        'institution_id',
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Alias event attribute to action.
     */
    public function getEventAttribute(): ?string
    {
        return $this->action;
    }
}
