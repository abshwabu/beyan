<?php

namespace Database\Seeders;

use App\Domain\Finance\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter (Nour)',
                'slug' => 'starter',
                'monthly_price' => 60000, // 600.00 ETB
                'yearly_price' => 600000,  // 6,000.00 ETB (save 17%)
                'currency' => 'ETB',
                'student_limit' => 50,
                'badge' => null,
                'is_active' => true,
                'sort_order' => 1,
                'features' => [
                    'Up to 50 active students',
                    'Digital student directory & guardian contacts',
                    '1-tap daily attendance marking',
                    'Sabaq memorization tracking',
                    'Cash & bank receipt generation',
                    'Basic monthly reporting',
                ],
            ],
            [
                'name' => 'Growth (Beyan)',
                'slug' => 'growth',
                'monthly_price' => 140000, // 1,400.00 ETB
                'yearly_price' => 1400000,  // 14,000.00 ETB (save 17%)
                'currency' => 'ETB',
                'student_limit' => 250,
                'badge' => 'Recommended',
                'is_active' => true,
                'sort_order' => 2,
                'features' => [
                    'Up to 250 active students',
                    'Complete Qur\'an module (Sabaq, Sabqi, Manzil)',
                    'Under-20s teacher fast-entry screen',
                    'Dual-curriculum timetables & conflict detection',
                    'CBE & Telebirr tuition ledger & reminders',
                    'Parent portal access',
                    'Full Excel (CSV) and printable PDF reports',
                ],
            ],
            [
                'name' => 'Institute (Firdaws)',
                'slug' => 'institute',
                'monthly_price' => 280000, // 2,800.00 ETB
                'yearly_price' => 2800000,  // 28,000.00 ETB (save 17%)
                'currency' => 'ETB',
                'student_limit' => null, // Unlimited
                'badge' => 'Enterprise',
                'is_active' => true,
                'sort_order' => 3,
                'features' => [
                    'Unlimited students & teachers',
                    'Multi-campus & hybrid class management',
                    'Custom fee schedules, discounts & scholarships',
                    'Full audit logs & multi-role permissions',
                    'Priority phone support & staff onboarding',
                    'Custom data migration from paper/Excel',
                ],
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }
    }
}
