<?php

namespace App\Domain\Platform\Services;

use App\Domain\Finance\Models\Plan;
use App\Domain\Institution\Models\Institution;
use App\Domain\Students\Models\Student;
use Illuminate\Validation\ValidationException;

class PlanLimitService
{
    /**
     * Check if an institution can add more students under its current plan.
     */
    public function canAddStudent(Institution $institution): bool
    {
        $limit = $this->getStudentLimit($institution);

        if ($limit === null) {
            return true;
        }

        $currentCount = $this->getCurrentStudentCount($institution);

        return $currentCount < $limit;
    }

    /**
     * Assert that the institution can add a student or throw a friendly validation exception.
     *
     * @throws ValidationException
     */
    public function ensureCanAddStudent(Institution $institution): void
    {
        if (! $this->canAddStudent($institution)) {
            $limit = $this->getStudentLimit($institution);
            $planName = $this->getPlanName($institution);

            throw ValidationException::withMessages([
                'plan_limit' => "Your institution has reached the maximum student limit ({$limit}) for the {$planName} plan. Please upgrade your subscription to register more students.",
            ]);
        }
    }

    /**
     * Get the student limit for an institution.
     */
    public function getStudentLimit(Institution $institution): ?int
    {
        // 1. Check latest subscription
        $subscription = $institution->latestSubscription;
        if ($subscription && $subscription->plan) {
            return $subscription->plan->student_limit;
        }

        // 2. Check institution subscription_plan slug
        if ($institution->subscription_plan) {
            $plan = Plan::where('slug', $institution->subscription_plan)->first();
            if ($plan) {
                return $plan->student_limit;
            }
        }

        // Default: If trial or pilot without specific plan, default to 50
        if ($institution->subscription_plan === 'trial') {
            return 50;
        }

        return null;
    }

    /**
     * Get the current count of active/enrolled students.
     */
    public function getCurrentStudentCount(Institution $institution): int
    {
        return Student::withoutInstitutionScope()
            ->where('institution_id', $institution->id)
            ->whereIn('status', ['active', 'suspended'])
            ->count();
    }

    /**
     * Get human-readable plan name.
     */
    public function getPlanName(Institution $institution): string
    {
        $subscription = $institution->latestSubscription;
        if ($subscription && $subscription->plan) {
            return $subscription->plan->name;
        }

        if ($institution->subscription_plan) {
            $plan = Plan::where('slug', $institution->subscription_plan)->first();
            if ($plan) {
                return $plan->name;
            }

            return ucfirst($institution->subscription_plan);
        }

        return 'Starter';
    }

    /**
     * Get complete usage summary for institution.
     *
     * @return array<string, mixed>
     */
    public function getUsageSummary(Institution $institution): array
    {
        $limit = $this->getStudentLimit($institution);
        $count = $this->getCurrentStudentCount($institution);

        return [
            'plan_name' => $this->getPlanName($institution),
            'student_count' => $count,
            'student_limit' => $limit,
            'is_unlimited' => $limit === null,
            'is_limit_reached' => $limit !== null && $count >= $limit,
            'usage_percentage' => $limit ? min(100, round(($count / $limit) * 100)) : 0,
        ];
    }
}
