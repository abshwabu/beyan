<?php

namespace App\Domain\Finance\Policies;

use App\Domain\Auth\Models\User;
use App\Domain\Finance\Models\FeeInvoice;

class FinancePolicy
{
    /**
     * Determine if user can view any finance resources (restricted to Accountant, Admin, Owner).
     */
    public function viewAny(User $user): bool
    {
        return $user->can('finance:view');
    }

    /**
     * Determine if user can view a specific invoice.
     */
    public function view(User $user, FeeInvoice $invoice): bool
    {
        if ($user->institution_id !== $invoice->institution_id) {
            return false;
        }

        // Owner, Admin, Accountant
        if ($user->can('finance:view')) {
            return true;
        }

        // Teachers have no access to finance
        if ($user->isTeacher()) {
            return false;
        }

        // Student viewing own fee invoice
        if ($user->hasRole('Student') && $invoice->student && $invoice->student->user_id === $user->id) {
            return $user->can('finance:view-reports');
        }

        // Guardian viewing their child's fee invoice
        if ($user->hasRole('Guardian') && $invoice->student && (
            $invoice->student->guardian_id === $user->id ||
            $invoice->student->guardians()->where('guardians.user_id', $user->id)->exists()
        )) {
            return $user->can('finance:view-reports');
        }

        return false;
    }

    /**
     * Determine if user can create invoices or fee plans.
     */
    public function create(User $user): bool
    {
        return $user->can('finance:manage-fees');
    }

    /**
     * Determine if user can update invoices.
     */
    public function update(User $user, FeeInvoice $invoice): bool
    {
        if ($user->institution_id !== $invoice->institution_id) {
            return false;
        }

        return $user->can('finance:manage-fees');
    }

    /**
     * Determine if user can delete/void an invoice.
     */
    public function delete(User $user, FeeInvoice $invoice): bool
    {
        if ($user->institution_id !== $invoice->institution_id) {
            return false;
        }

        return $user->can('finance:manage-fees') && $user->isOwner();
    }

    /**
     * Determine if user can record payments.
     */
    public function recordPayment(User $user): bool
    {
        return $user->can('finance:record-payment');
    }

    /**
     * Determine if user can view financial reports (Income vs Expense).
     */
    public function viewReports(User $user): bool
    {
        return $user->can('finance:view') && $user->can('finance:view-reports');
    }

    /**
     * Determine if user can manage fee plans and student discounts.
     */
    public function managePlans(User $user): bool
    {
        return $user->can('finance:manage-fees');
    }

    /**
     * Determine if user can manage expenses.
     */
    public function manageExpenses(User $user): bool
    {
        return $user->can('finance:manage-fees') || ($user->can('finance:view') && $user->can('finance:record-payment'));
    }
}
