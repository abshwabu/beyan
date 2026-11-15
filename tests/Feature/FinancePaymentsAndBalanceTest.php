<?php

namespace Tests\Feature;

use App\Domain\Auth\Models\User;
use App\Domain\Auth\Services\RolePermissionMatrix;
use App\Domain\Finance\Actions\RecordPaymentAction;
use App\Domain\Finance\Actions\RecordPaymentReversalAction;
use App\Domain\Finance\Models\FeeInvoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\Institution\Models\Institution;
use App\Domain\Institution\Tenancy\TenantManager;
use App\Domain\Students\Models\Student;
use Database\Seeders\QuranReferenceSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FinancePaymentsAndBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $accountantUser;

    protected Student $student;

    protected FeeInvoice $invoice;

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

        $this->accountantUser = User::create([
            'institution_id' => $this->institution->id,
            'name' => 'Sister Amina (Accountant)',
            'email' => 'accountant@beyan.edu.et',
            'role' => 'accountant',
            'password' => bcrypt('password'),
        ]);
        $this->accountantUser->assignRole('Accountant');

        $this->student = Student::create([
            'institution_id' => $this->institution->id,
            'first_name' => 'Bilal',
            'last_name' => 'Tariq',
            'full_name' => 'Bilal Tariq',
            'date_of_birth' => '2014-06-15',
            'gender' => 'male',
            'admission_date' => '2024-09-01',
            'status' => 'active',
        ]);

        // Create an invoice of 1,000.00 ETB (100,000 minor units)
        $this->invoice = FeeInvoice::create([
            'institution_id' => $this->institution->id,
            'student_id' => $this->student->id,
            'invoice_number' => 'INV-2026-0001',
            'title' => 'Term 1 Tuition',
            'period' => '2026-T1',
            'subtotal' => 100000,
            'discount_total' => 0,
            'total_amount' => 100000,
            'amount' => 1000,
            'paid_amount' => 0,
            'balance_due' => 100000,
            'status' => 'unpaid',
            'due_date' => '2026-12-01',
        ]);
    }

    public function test_double_clicked_record_payment_never_creates_two_payments(): void
    {
        $this->actingAs($this->accountantUser);

        $idempotencyKey = (string) Str::uuid();

        // First click
        $response1 = $this->postJson(route('finance.invoices.payments.store', $this->invoice->id), [
            'amount_minor_units' => 40000, // 400 ETB
            'payment_method' => 'cash',
            'reference_number' => 'CSH-001',
            'date' => '2026-11-15',
            'idempotency_key' => $idempotencyKey,
        ]);

        $response1->assertCreated();
        $this->assertEquals(60000, $response1->json('balance_due'));
        $this->assertEquals('partial', $response1->json('status'));

        // Immediate duplicate click with exact same idempotency_key
        $response2 = $this->postJson(route('finance.invoices.payments.store', $this->invoice->id), [
            'amount_minor_units' => 40000,
            'payment_method' => 'cash',
            'reference_number' => 'CSH-001',
            'date' => '2026-11-15',
            'idempotency_key' => $idempotencyKey,
        ]);

        // Response should succeed idempotently without creating a second payment
        $response2->assertOk();
        $this->assertEquals($response1->json('payment.id'), $response2->json('payment.id'));

        // Assert exactly ONE payment exists in the database
        $this->assertDatabaseCount('payments', 1);

        // Verify database unique index violation if attempted directly at DB level
        $this->expectException(QueryException::class);
        Payment::create([
            'institution_id' => $this->institution->id,
            'fee_invoice_id' => $this->invoice->id,
            'payment_number' => 'RCP-FORCED-DUP',
            'amount' => 40000,
            'payment_method' => 'cash',
            'date' => '2026-11-15',
            'recorded_by' => $this->accountantUser->id,
            'idempotency_key' => $idempotencyKey, // Duplicate key!
            'type' => 'payment',
        ]);
    }

    public function test_balances_update_correctly_after_partial_payments_and_refunds(): void
    {
        $this->actingAs($this->accountantUser);

        // Initial state: Unpaid, 100,000 minor units due
        $this->assertEquals('unpaid', $this->invoice->status);
        $this->assertEquals(100000, $this->invoice->balance_due);

        // Step 1: Record partial payment of 300 ETB (30,000 minor units)
        $action = app(RecordPaymentAction::class);
        $payment1 = $action->execute([
            'fee_invoice_id' => $this->invoice->id,
            'amount_minor_units' => 30000,
            'payment_method' => 'telebirr',
            'reference_number' => 'TB-9821',
            'date' => '2026-11-15',
            'recorded_by' => $this->accountantUser->id,
            'idempotency_key' => 'step-1-part-pay',
        ]);

        $this->invoice->refresh();
        $this->assertEquals('partial', $this->invoice->status);
        $this->assertEquals(70000, $this->invoice->balance_due);

        // Step 2: Record remaining payment of 700 ETB (70,000 minor units)
        $payment2 = $action->execute([
            'fee_invoice_id' => $this->invoice->id,
            'amount_minor_units' => 70000,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'CBE-TRANS-441',
            'date' => '2026-11-15',
            'recorded_by' => $this->accountantUser->id,
            'idempotency_key' => 'step-2-full-pay',
        ]);

        $this->invoice->refresh();
        $this->assertEquals('paid', $this->invoice->status);
        $this->assertEquals(0, $this->invoice->balance_due);

        // Step 3: Issue refund of 250 ETB (25,000 minor units) on the second payment
        $reversalAction = app(RecordPaymentReversalAction::class);
        $refund = $reversalAction->execute([
            'original_payment_id' => $payment2->id,
            'amount_minor_units' => 25000,
            'type' => 'refund',
            'reason' => 'Overpayment adjustment',
            'idempotency_key' => 'step-3-refund',
            'recorded_by' => $this->accountantUser->id,
        ]);

        $this->invoice->refresh();
        // Balance due increases from 0 to 25,000 minor units, and status transitions back to partial
        $this->assertEquals('partial', $this->invoice->status);
        $this->assertEquals(25000, $this->invoice->balance_due);

        // Step 4: Issue reversal for the remaining 45,000 on payment 2 and 30,000 on payment 1
        $reversalAction->execute([
            'original_payment_id' => $payment2->id,
            'amount_minor_units' => 45000,
            'type' => 'refund',
            'reason' => 'Student withdrawal full refund',
            'idempotency_key' => 'step-4-refund-p2-remainder',
            'recorded_by' => $this->accountantUser->id,
        ]);
        $reversalAction->execute([
            'original_payment_id' => $payment1->id,
            'amount_minor_units' => 30000,
            'type' => 'refund',
            'reason' => 'Student withdrawal full refund p1',
            'idempotency_key' => 'step-4-refund-p1',
            'recorded_by' => $this->accountantUser->id,
        ]);

        $this->invoice->refresh();
        // Fully refunded: balance returns to 100,000 minor units, status reverts to unpaid
        $this->assertEquals('unpaid', $this->invoice->status);
        $this->assertEquals(100000, $this->invoice->balance_due);
    }

    public function test_payments_are_strictly_append_only_and_cannot_be_updated_or_deleted(): void
    {
        $action = app(RecordPaymentAction::class);
        $payment = $action->execute([
            'fee_invoice_id' => $this->invoice->id,
            'amount_minor_units' => 50000,
            'payment_method' => 'cash',
            'date' => '2026-11-15',
            'recorded_by' => $this->accountantUser->id,
            'idempotency_key' => 'append-only-test-1',
        ]);

        // Attempting to update a payment must throw RuntimeException
        try {
            $payment->update(['amount' => 60000]);
            $this->fail('Expected RuntimeException was not thrown on payment update.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Payments are append-only', $e->getMessage());
        }

        // Attempting to delete a payment must throw RuntimeException
        try {
            $payment->delete();
            $this->fail('Expected RuntimeException was not thrown on payment delete.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Payments are append-only', $e->getMessage());
        }
    }

    public function test_printable_receipt_endpoint_renders_receipt_successfully(): void
    {
        $this->actingAs($this->accountantUser);

        $action = app(RecordPaymentAction::class);
        $payment = $action->execute([
            'fee_invoice_id' => $this->invoice->id,
            'amount_minor_units' => 50000,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'CBE-RCPT-8821',
            'date' => '2026-11-15',
            'recorded_by' => $this->accountantUser->id,
            'idempotency_key' => 'receipt-test-key-1',
        ]);

        $response = $this->get(route('finance.payments.receipt', $payment->id));

        $response->assertOk();
        $response->assertSee('Official Receipt');
        $response->assertSee($payment->payment_number);
        $response->assertSee('Bilal Tariq');
        $response->assertSee('500.00 ETB');
        $response->assertSee('Remaining Balance');
    }
}
