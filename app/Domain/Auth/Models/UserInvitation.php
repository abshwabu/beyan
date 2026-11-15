<?php

namespace App\Domain\Auth\Models;

use App\Domain\Institution\Audit\Auditable;
use App\Domain\Institution\Tenancy\BelongsToInstitution;
use Database\Factories\UserInvitationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInvitation extends Model
{
    use Auditable, BelongsToInstitution, HasFactory, HasUlids;

    protected $table = 'user_invitations';

    protected $fillable = [
        'institution_id',
        'invited_by_id',
        'email',
        'roles',
        'token',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'roles' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    protected static function newFactory(): UserInvitationFactory
    {
        return UserInvitationFactory::new();
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && ! $this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function getAcceptUrlAttribute(): string
    {
        return url("/invitations/{$this->token}/accept");
    }
}
