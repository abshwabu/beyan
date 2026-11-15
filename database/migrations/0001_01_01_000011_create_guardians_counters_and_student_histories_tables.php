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
        // 1. Race-safe per-institution sequential counters
        Schema::create('counters', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('key');
            $table->integer('year');
            $table->unsignedInteger('value')->default(0);
            $table->timestamps();

            $table->unique(['institution_id', 'key', 'year']);
        });

        // 2. Guardians table
        Schema::create('guardians', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('user_id', 26)->nullable()->index();
            $table->string('full_name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('occupation')->nullable();
            $table->timestamps();

            $table->index('institution_id');
        });

        // 3. Many-to-many guardian_student pivot
        Schema::create('guardian_student', function (Blueprint $table) {
            $table->id();
            $table->string('institution_id', 26)->index();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUlid('guardian_id')->constrained('guardians')->cascadeOnDelete();
            $table->string('relationship_type')->default('guardian');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['student_id', 'guardian_id']);
        });

        // 4. Effective-dated student class enrollment history
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('academic_year_id', 26)->nullable();
            $table->string('class_name');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('current');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'student_id']);
            $table->index(['institution_id', 'class_name']);
        });

        // 5. Student status audit history
        Schema::create('student_status_histories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('changed_by_id', 26)->nullable();
            $table->string('previous_status', 20);
            $table->string('new_status', 20);
            $table->text('reason');
            $table->date('effective_date');
            $table->timestamps();

            $table->index(['institution_id', 'student_id']);
        });

        // 6. Private student document metadata
        Schema::create('student_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('uploaded_by_id', 26)->nullable();
            $table->string('title');
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type', 100);
            $table->timestamps();

            $table->index(['institution_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_documents');
        Schema::dropIfExists('student_status_histories');
        Schema::dropIfExists('student_enrollments');
        Schema::dropIfExists('guardian_student');
        Schema::dropIfExists('guardians');
        Schema::dropIfExists('counters');
    }
};
