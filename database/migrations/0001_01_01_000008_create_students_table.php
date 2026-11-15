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
        Schema::create('students', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('user_id', 26)->nullable()->index();
            $table->string('guardian_id', 26)->nullable()->index();
            $table->string('full_name');
            $table->string('name')->nullable();
            $table->string('student_code');
            $table->string('admission_number')->nullable();
            $table->string('gender', 10)->default('male');
            $table->date('date_of_birth')->nullable();
            $table->json('contact')->nullable();
            $table->date('admission_date')->nullable();
            $table->string('status', 20)->default('active'); // active|inactive|suspended|withdrawn|graduated
            $table->timestamps();

            $table->unique(['institution_id', 'student_code']);
            $table->unique(['institution_id', 'admission_number']);
            $table->index(['institution_id', 'status']);
            $table->index(['institution_id', 'full_name']);
            $table->index('institution_id');
        });

        Schema::create('student_teacher', function (Blueprint $table) {
            $table->id();
            $table->string('institution_id', 26)->index();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('teacher_id', 26)->index();
            $table->timestamps();

            $table->unique(['student_id', 'teacher_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_teacher');
        Schema::dropIfExists('students');
    }
};
