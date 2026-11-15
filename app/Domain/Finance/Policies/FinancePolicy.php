<?php

namespace App\Domain\Finance\Policies;

use App\Domain\Auth\Models\User;
use App\Domain\Finance\Models\FeeInvoice;

class FinancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('finance:view');
    }

    public function view(User $user, FeeInvoice $invoice): bool
    {
        if ($user->institution_id !== $invoice->institution_id) {
            return false;
        }

        // Owner, Admin, Accountant
        if ($user->can('finance:view')) {
            return true;
        }

        // Teachers have no access to student invoices
        if ($user->isTeacher()) {
            return false;
        }

        // Student viewing own fee invoice
        if ($user->hasRole('Student') && $invoice->student && $invoice->student->user_id === $user->id) {
            return $user->can('finance:view-reports');
        }

        // Guardian viewing their child's fee invoice
        if ($user->hasRole('Guardian') && $invoice->student && $invoice->student->guardian_id === $user->id) {
            return $user->can('finance:view-reports');
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('finance:manage-fees');
    }

    public function update(User $user, FeeInvoice $invoice): bool
    {
        if ($user->institution_id !== $invoice->institution_id) {
            return false;
        }

        return $user->can('finance:manage-fees');
    }

    public function delete(User $user, FeeInvoice $invoice): bool
    {
        if ($user->institution_id !== $invoice->institution_id) {
            return false;
        }

        return $user->can('finance:manage-fees') && $user->isOwner();
    }
}
