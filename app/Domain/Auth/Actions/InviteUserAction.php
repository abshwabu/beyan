<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Mail\UserInvitationMail;
use App\Domain\Auth\Models\UserInvitation;
use App\Domain\Institution\Tenancy\TenantManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;

class InviteUserAction
{
    /**
     * Create an invitation and dispatch the invitation email after the transaction commits.
     *
     * @param  array{
     *     email: string,
     *     roles: array<string>,
     *     institution_id?: ?string,
     *     invited_by_id?: ?string,
     *     expires_in_days?: ?int
     * }  $data
     */
    public function execute(array $data): UserInvitation
    {
        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);

        $institutionId = $data['institution_id'] ?? $tenantManager->getTenantId();

        if (empty($institutionId)) {
            throw new InvalidArgumentException('An institution ID is required to invite a user.');
        }

        if (empty($data['roles'])) {
            throw new InvalidArgumentException('At least one role must be specified for the invitation.');
        }

        $invitation = DB::transaction(function () use ($data, $institutionId) {
            $token = Str::random(64);
            $days = $data['expires_in_days'] ?? 7;

            $invitation = UserInvitation::withoutInstitutionScope()->create([
                'institution_id' => $institutionId,
                'invited_by_id' => $data['invited_by_id'] ?? auth()->id(),
                'email' => strtolower(trim($data['email'])),
                'roles' => $data['roles'],
                'token' => $token,
                'status' => 'pending',
                'expires_at' => Carbon::now()->addDays($days),
            ]);

            // Guaranteed: send the email strictly after transaction commits
            DB::afterCommit(function () use ($invitation) {
                Mail::to($invitation->email)->send(new UserInvitationMail($invitation));
            });

            return $invitation;
        });

        return $invitation;
    }
}
