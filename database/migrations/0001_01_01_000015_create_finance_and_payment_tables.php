<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Fee Plans (per course or program: monthly|term|one-time)
        Schema::create('fee_plans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignUlid('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('period_type', 20); // monthly|term|one-time
            $table->unsignedBigInteger('amount'); // Minor units (e.g. cents)
            $table->string('currency', 3)->default('ETB');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['institution_id', 'is_active']);
            $table->index(['institution_id', 'period_type']);
        });

        // 2. Student Discounts & Scholarships
        Schema::create('student_discounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUlid('fee_plan_id')->nullable()->constrained('fee_plans')->nullOnDelete();
            $table->string('name');
            $table->string('discount_type', 20); // percentage|fixed_amount
            $table->unsignedBigInteger('discount_value'); // e.g. 20 for 20%, or 50000 for 500 ETB in minor units
            $table->text('reason')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'student_id', 'is_active']);
        });

        // 3. Enhance fee_invoices table for plan linking, period, minor units, and unique index
        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->foreignUlid('fee_plan_id')->nullable()->after('student_id')->constrained('fee_plans')->nullOnDelete();
            $table->string('period', 30)->nullable()->after('fee_plan_id'); // e.g. '2026-11' or 'TERM-1-2026'
            $table->unsignedBigInteger('subtotal')->default(0)->after('title');
            $table->unsignedBigInteger('discount_total')->default(0)->after('subtotal');
            $table->unsignedBigInteger('total_amount')->default(0)->after('discount_total');
            $table->unsignedBigInteger('balance_due')->default(0)->after('paid_amount');
            $table->text('notes')->nullable()->after('due_date');
            $table->timestamp('last_reminder_sent_at')->nullable()->after('notes');

            // Unique index to prevent duplicate invoice generation per student per fee plan per period
            $table->unique(['institution_id', 'student_id', 'fee_plan_id', 'period'], 'uq_inv_inst_student_plan_period');
        });

        // 4. Invoice Items
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignUlid('fee_invoice_id')->constrained('fee_invoices')->cascadeOnDelete();
            $table->foreignUlid('fee_plan_id')->nullable()->constrained('fee_plans')->nullOnDelete();
            $table->string('description');
            $table->unsignedBigInteger('amount'); // Minor units
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('final_amount'); // Minor units
            $table->timestamps();

            $table->index(['institution_id', 'fee_invoice_id']);
        });

        // 5. Payments (Append-only with idempotency key)
        Schema::create('payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignUlid('fee_invoice_id')->constrained('fee_invoices')->cascadeOnDelete();
            $table->string('payment_number');
            $table->unsignedBigInteger('amount'); // Minor units
            $table->string('payment_method', 30); // cash|bank_transfer|other
            $table->string('reference_number')->nullable();
            $table->date('date');
            $table->foreignUlid('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->string('idempotency_key');
            $table->string('type', 20)->default('payment'); // payment|refund|reversal
            $table->foreignUlid('reversal_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['institution_id', 'idempotency_key'], 'uq_payments_idempotency');
            $table->index(['institution_id', 'fee_invoice_id']);
            $table->index(['institution_id', 'date']);
            $table->index(['institution_id', 'type']);
        });

        // 6. Expenses
        Schema::create('expenses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('category', 50); // salaries|rent|utilities|maintenance|supplies|other
            $table->string('title');
            $table->unsignedBigInteger('amount'); // Minor units
            $table->date('date');
            $table->string('payment_method', 30)->default('cash');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUlid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['institution_id', 'category', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');

        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->dropUnique('uq_inv_inst_student_plan_period');
            $table->dropForeign(['fee_plan_id']);
            $table->dropColumn([
                'fee_plan_id',
                'period',
                'subtotal',
                'discount_total',
                'total_amount',
                'balance_due',
                'notes',
                'last_reminder_sent_at',
            ]);
        });

        Schema::dropIfExists('student_discounts');
        Schema::dropIfExists('fee_plans');
    }
};
