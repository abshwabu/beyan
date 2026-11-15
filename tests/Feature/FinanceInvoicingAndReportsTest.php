<?php

namespace Tests\Feature;

use App\Domain\Auth\Models\User;
use App\Domain\Auth\Services\RolePermissionMatrix;
use App\Domain\Finance\Actions\GenerateInvoiceAction;
use App\Domain\Finance\Actions\RecordPaymentAction;
use App\Domain\Finance\Actions\RecordPaymentReversalAction;
use App\Domain\Finance\Models\Expense;
use App\Domain\Finance\Models\FeeInvoice;
use App\Domain\Finance\Models\FeePlan;
use App\Domain\Finance\Models\StudentDiscount;
use App\Domain\Finance\Services\FinancialReportingService;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Students\Models\Student;
use Database\Seeders\QuranReferenceSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FinanceInvoicingAndReportsTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $ownerUser;

    protected User $adminUser;

    protected User $accountantUser;

    protected User $teacherUser;

    protected User $coordinatorUser;

    protected User $receptionistUser;

    protected Student $student;

    protected FeePlan $feePlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(QuranReferenceSeeder::class);

        $this->institution = Institution::create([
            'name' => 'Beyan Academy',
            'slug' => 'beyan',
            'contact' => ['email' => 'admin@beyan.edu.et', 'phone' => '+251911000000'],
            'mode' => 'physical',
            'timezone' => 'Africa/Addis_Ababa',
            'currency' => 'ETB',
            'locale' => 'am',
            'status' => 'active',
            'subscription_plan' => 'standard',
        ]);

        app(TenantManager::class)->setTenant($this->institution);

        $matrix = new RolePermissionMatrix;
        $matrix->seedPermissions();
        $matrix->seedInstitutionRoles($this->institution->id);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->institution->id);

        // 1. Owner
        $this->ownerUser = User::create([
            'institution_id' => $this->institution->id,
            'name' => 'Sheikh Ahmed (Owner)',
            'email' => 'owner@beyan.edu.et',
            'role' => 'owner',
            'password' => bcrypt('password'),
        ]);
        $this->ownerUser->assignRole('Owner');

        // 2. Admin
        $this->adminUser = User::create([
            'institution_id' => $this->institution->id,
            'name' => 'Ustaz Khalid (Admin)',
            'email' => 'admin@beyan.edu.et',
            'role' => 'admin',
            'password' => bcrypt('password'),
        ]);
        $this->adminUser->assignRole('Admin');

        // 3. Accountant
        $this->accountantUser = User::create([
            'institution_id' => $this->institution->id,
            'name' => 'Sister Amina (Accountant)',
            'email' => 'accountant@beyan.edu.et',
            'role' => 'accountant',
            'password' => bcrypt('password'),
        ]);
        $this->accountantUser->assignRole('Accountant');

        // 4. Teacher
        $this->teacherUser = User::create([
            'institution_id' => $this->institution->id,
            'name' => 'Ustaz Tariq (Teacher)',
            'email' => 'teacher@beyan.edu.et',
            'role' => 'teacher',
            'password' => bcrypt('password'),
        ]);
        $this->teacherUser->assignRole('Teacher');

        // 5. Academic Coordinator
        $this->coordinatorUser = User::create([
            'institution_id' => $this->institution->id,
            'name' => 'Brother Ibrahim (Coordinator)',
            'email' => 'coordinator@beyan.edu.et',
            'role' => 'academic_coordinator',
            'password' => bcrypt('password'),
        ]);
        $this->coordinatorUser->assignRole('Academic Coordinator');

        // 6. Receptionist
        $this->receptionistUser = User::create([
            'institution_id' => $this->institution->id,
            'name' => 'Sister Huda (Receptionist)',
            'email' => 'receptionist@beyan.edu.et',
            'role' => 'receptionist',
            'password' => bcrypt('password'),
        ]);
        $this->receptionistUser->assignRole('Receptionist');

        $this->student = Student::create([
            'institution_id' => $this->institution->id,
            'first_name' => 'Maryam',
            'last_name' => 'Zubair',
            'full_name' => 'Maryam Zubair',
            'date_of_birth' => '2015-08-20',
            'gender' => 'female',
            'admission_date' => '2024-09-01',
            'status' => 'active',
        ]);

        $this->feePlan = FeePlan::create([
            'institution_id' => $this->institution->id,
            'name' => 'Qur\'an Full-Time Program Fee',
            'code' => 'FP-QUR-M',
            'period_type' => 'monthly',
            'amount' => 200000, // 2,000.00 ETB in minor units
            'currency' => 'ETB',
            'description' => 'Standard monthly fee for full-time Qur\'an program',
            'is_active' => true,
        ]);
    }

    public function test_fee_plan_invoice_generation_and_discount_calculation(): void
    {
        $this->actingAs($this->accountantUser);

        // Apply a 25% Sibling Discount
        StudentDiscount::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'fee_plan_id' => $this->feePlan->id,
            'name' => 'Sibling Discount (25%)',
            'discount_type' => 'percentage',
            'discount_value' => 25,
            'is_active' => true,
        ]);

        $action = app(GenerateInvoiceAction::class);
        $invoice = $action->execute([
            'student_id' => $this->student->id,
            'fee_plan_id' => $this->feePlan->id,
            'period' => '2026-11',
            'due_date' => '2026-11-30',
        ]);

        // Base 200,000 minor units - 25% (50,000) = 150,000 minor units
        $this->assertEquals(200000, $invoice->subtotal);
        $this->assertEquals(50000, $invoice->discount_total);
        $this->assertEquals(150000, $invoice->total_amount);
        $this->assertEquals(150000, $invoice->balance_due);
        $this->assertEquals('unpaid', $invoice->status);
        $this->assertCount(1, $invoice->items);

        $item = $invoice->items->first();
        $this->assertEquals(200000, $item->amount);
        $this->assertEquals(50000, $item->discount_amount);
        $this->assertEquals(150000, $item->final_amount);
    }

    public function test_duplicate_invoice_generation_for_same_student_plan_and_period_is_prevented(): void
    {
        $action = app(GenerateInvoiceAction::class);

        // First generation succeeds
        $invoice = $action->execute([
            'student_id' => $this->student->id,
            'fee_plan_id' => $this->feePlan->id,
            'period' => '2026-11',
        ]);
        $this->assertNotNull($invoice);

        // Second generation attempt for exact same student, fee plan, and period must throw ValidationException
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('An invoice already exists for student Maryam Zubair');

        $action->execute([
            'student_id' => $this->student->id,
            'fee_plan_id' => $this->feePlan->id,
            'period' => '2026-11',
        ]);
    }

    public function test_database_unique_index_rejects_duplicate_invoice_on_raw_insert(): void
    {
        // First invoice exists
        FeeInvoice::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'fee_plan_id' => $this->feePlan->id,
            'invoice_number' => 'INV-FIRST-01',
            'title' => 'First',
            'period' => '2026-12',
            'subtotal' => 100000,
            'total_amount' => 100000,
            'balance_due' => 100000,
            'status' => 'unpaid',
            'due_date' => '2026-12-15',
        ]);

        // Attempt second insert with same (institution_id, student_id, fee_plan_id, period)
        $this->expectException(QueryException::class);

        FeeInvoice::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'fee_plan_id' => $this->feePlan->id,
            'invoice_number' => 'INV-SECOND-02',
            'title' => 'Second',
            'period' => '2026-12', // Duplicate tuple!
            'subtotal' => 100000,
            'total_amount' => 100000,
            'balance_due' => 100000,
            'status' => 'unpaid',
            'due_date' => '2026-12-15',
        ]);
    }

    public function test_income_vs_expense_report_aggregates_net_financial_position(): void
    {
        $this->actingAs($this->accountantUser);

        // 1. Create invoice and payments
        $invoice = FeeInvoice::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'fee_plan_id' => $this->feePlan->id,
            'invoice_number' => 'INV-2026-0099',
            'title' => 'Tuition',
            'period' => '2026-11',
            'subtotal' => 500000,
            'total_amount' => 500000,
            'balance_due' => 500000,
            'status' => 'unpaid',
            'due_date' => '2026-11-20',
        ]);

        $paymentAction = app(RecordPaymentAction::class);

        // Payment 1: 3,000 ETB (300,000 minor units) via Bank Transfer
        $p1 = $paymentAction->execute([
            'fee_invoice_id' => $invoice->id,
            'amount_minor_units' => 300000,
            'payment_method' => 'bank_transfer',
            'date' => '2026-11-10',
            'recorded_by' => $this->accountantUser->id,
            'idempotency_key' => 'p1-rpt-key',
        ]);

        // Payment 2: 2,000 ETB (200,000 minor units) via Cash
        $p2 = $paymentAction->execute([
            'fee_invoice_id' => $invoice->id,
            'amount_minor_units' => 200000,
            'payment_method' => 'cash',
            'date' => '2026-11-12',
            'recorded_by' => $this->accountantUser->id,
            'idempotency_key' => 'p2-rpt-key',
        ]);

        // Refund: 500 ETB (50,000 minor units) on payment 1
        $reversalAction = app(RecordPaymentReversalAction::class);
        $reversalAction->execute([
            'original_payment_id' => $p1->id,
            'amount_minor_units' => 50000,
            'type' => 'refund',
            'reason' => 'Fee discount adjustment',
            'idempotency_key' => 'ref-rpt-key',
            'recorded_by' => $this->accountantUser->id,
            'date' => '2026-11-14',
        ]);

        // Total Net Income: (300,000 + 200,000) - 50,000 = 450,000 minor units (4,500 ETB)

        // 2. Record Operational Expenses
        Expense::create([
            'institution_id' => $this->institution->id,
            'category' => 'salaries',
            'title' => 'Teachers Monthly Salaries',
            'amount' => 200000, // 2,000 ETB
            'date' => '2026-11-15',
            'payment_method' => 'bank_transfer',
            'recorded_by' => $this->accountantUser->id,
        ]);

        Expense::create([
            'institution_id' => $this->institution->id,
            'category' => 'utilities',
            'title' => 'Electricity & Water Bill',
            'amount' => 50000, // 500 ETB
            'date' => '2026-11-15',
            'payment_method' => 'cash',
            'recorded_by' => $this->accountantUser->id,
        ]);

        // Total Expenses: 200,000 + 50,000 = 250,000 minor units (2,500 ETB)
        // Net Surplus: 450,000 - 250,000 = 200,000 minor units (2,000 ETB)

        $reportingService = app(FinancialReportingService::class);
        $report = $reportingService->getIncomeExpenseReport($this->institution, '2026-11-01', '2026-11-30');

        $this->assertEquals(500000, $report['income']['gross_amount']);
        $this->assertEquals(50000, $report['income']['refunds_amount']);
        $this->assertEquals(450000, $report['income']['net_amount']);

        $this->assertEquals(250000, $report['expenses']['total_amount']);
        $this->assertEquals(200000, $report['expenses']['by_category']['salaries']);
        $this->assertEquals(50000, $report['expenses']['by_category']['utilities']);

        $this->assertEquals(200000, $report['net_surplus_deficit']);
        $this->assertTrue($report['is_surplus']);

        // Test API endpoint returns same data
        $response = $this->getJson(route('finance.reports.income-expense', [
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
        ]));
        $response->assertOk();
        $response->assertJsonPath('net_surplus_deficit', 200000);
        $response->assertJsonPath('is_surplus', true);
    }

    public function test_overdue_invoices_and_reminders_list(): void
    {
        $this->actingAs($this->accountantUser);

        // Create overdue invoice (due 10 days ago)
        $overdueInvoice = FeeInvoice::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'invoice_number' => 'INV-OVERDUE-1',
            'title' => 'Late Tuition',
            'subtotal' => 100000,
            'total_amount' => 100000,
            'balance_due' => 100000,
            'status' => 'unpaid',
            'due_date' => now()->subDays(10)->toDateString(),
        ]);

        // Create future invoice (due in 10 days)
        $futureInvoice = FeeInvoice::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'invoice_number' => 'INV-FUTURE-2',
            'title' => 'Upcoming Tuition',
            'subtotal' => 100000,
            'total_amount' => 100000,
            'balance_due' => 100000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(10)->toDateString(),
        ]);

        $response = $this->getJson(route('finance.reminders.index'));
        $response->assertOk();

        // Overdue list should include overdueInvoice but NOT futureInvoice
        $ids = collect($response->json())->pluck('id');
        $this->assertTrue($ids->contains($overdueInvoice->id));
        $this->assertFalse($ids->contains($futureInvoice->id));

        // Dispatch payment reminder
        $remindResponse = $this->postJson(route('finance.invoices.remind', $overdueInvoice->id), [
            'channel' => 'sms',
        ]);

        $remindResponse->assertOk();
        $remindResponse->assertJsonPath('result.status', 'queued_or_sent');

        $overdueInvoice->refresh();
        $this->assertNotNull($overdueInvoice->last_reminder_sent_at);
    }

    public function test_only_accountant_admin_and_owner_can_see_finance(): void
    {
        // 1. Owner: Allowed
        $this->actingAs($this->ownerUser);
        $this->getJson(route('finance.invoices.index'))->assertOk();
        $this->getJson(route('finance.reports.income-expense'))->assertOk();

        // 2. Admin: Allowed
        $this->actingAs($this->adminUser);
        $this->getJson(route('finance.invoices.index'))->assertOk();
        $this->getJson(route('finance.reports.income-expense'))->assertOk();

        // 3. Accountant: Allowed
        $this->actingAs($this->accountantUser);
        $this->getJson(route('finance.invoices.index'))->assertOk();
        $this->getJson(route('finance.reports.income-expense'))->assertOk();

        // 4. Teacher: Forbidden (403)
        $this->actingAs($this->teacherUser);
        $this->getJson(route('finance.invoices.index'))->assertStatus(403);
        $this->getJson(route('finance.reports.income-expense'))->assertStatus(403);

        // 5. Academic Coordinator: Forbidden (403)
        $this->actingAs($this->coordinatorUser);
        $this->getJson(route('finance.invoices.index'))->assertStatus(403);
        $this->getJson(route('finance.reports.income-expense'))->assertStatus(403);

        // 6. Receptionist: Forbidden from viewing general invoices and reports (403)
        $this->actingAs($this->receptionistUser);
        $this->getJson(route('finance.invoices.index'))->assertStatus(403);
        $this->getJson(route('finance.reports.income-expense'))->assertStatus(403);
    }
}
