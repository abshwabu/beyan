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
        Schema::create('fee_invoices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('invoice_number');
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('pending'); // pending, paid, partial, cancelled
            $table->date('due_date');
            $table->timestamps();

            $table->unique(['institution_id', 'invoice_number']);
            $table->index('institution_id');
            $table->index('student_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_invoices');
    }
};
