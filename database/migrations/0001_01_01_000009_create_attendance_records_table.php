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
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('academic_year_id', 26)->nullable()->index();
            $table->date('date');
            $table->string('status', 20)->default('present'); // present, absent, late, excused
            $table->string('remarks')->nullable();
            $table->string('recorded_by', 26)->nullable()->index();
            $table->timestamps();

            $table->unique(['institution_id', 'student_id', 'date']);
            $table->index('institution_id');
            $table->index('student_id');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
