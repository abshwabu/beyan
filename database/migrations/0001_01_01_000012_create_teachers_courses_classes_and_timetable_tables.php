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
        // 1. Teachers table
        Schema::create('teachers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('user_id', 26)->nullable()->index();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('qualifications')->nullable();
            $table->string('specialization')->nullable();
            $table->string('status', 20)->default('active'); // active|inactive|on_leave
            $table->json('availability_slots')->nullable();
            $table->text('bio')->nullable();
            $table->timestamps();

            $table->index('institution_id');
            $table->index(['institution_id', 'status']);
        });

        // 2. Courses table
        Schema::create('courses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('level', 30)->default('all_levels');
            $table->string('duration', 50)->nullable();
            $table->json('learning_objectives')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['institution_id', 'code']);
            $table->index('institution_id');
            $table->index(['institution_id', 'status']);
        });

        // 3. Class Groups table
        Schema::create('class_groups', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignUlid('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('academic_term_id', 26)->nullable()->index();
            $table->string('name');
            $table->unsignedInteger('capacity')->default(20);
            $table->string('type', 20)->default('physical'); // online|physical|hybrid
            $table->string('meeting_link')->nullable();
            $table->string('room')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index('institution_id');
            $table->index(['institution_id', 'status']);
        });

        // 4. Class Group to Teacher assignment pivot
        Schema::create('class_group_teacher', function (Blueprint $table) {
            $table->id();
            $table->string('institution_id', 26)->index();
            $table->foreignUlid('class_group_id')->constrained('class_groups')->cascadeOnDelete();
            $table->foreignUlid('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->boolean('is_primary')->default(true);
            $table->timestamps();

            $table->unique(['class_group_id', 'teacher_id']);
        });

        // 5. Effective-dated student class enrollment
        Schema::create('class_enrollments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUlid('class_group_id')->constrained('class_groups')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('enrolled'); // enrolled|transferred|completed|dropped
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'student_id']);
            $table->index(['institution_id', 'class_group_id']);
        });

        // 6. Recurring class schedule template
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignUlid('class_group_id')->constrained('class_groups')->cascadeOnDelete();
            $table->foreignUlid('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->unsignedTinyInteger('weekday'); // 0=Sunday, 1=Monday, ..., 6=Saturday
            $table->time('start_time');
            $table->time('end_time');
            $table->string('timezone', 50)->default('Africa/Addis_Ababa');
            $table->timestamps();

            $table->index(['institution_id', 'class_group_id']);
        });

        // 7. Timezone-aware Class Sessions (stored in UTC)
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignUlid('class_group_id')->constrained('class_groups')->cascadeOnDelete();
            $table->foreignUlid('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->date('scheduled_date');
            $table->dateTime('start_time_utc');
            $table->dateTime('end_time_utc');
            $table->string('type', 20)->default('physical'); // online|physical|hybrid
            $table->string('meeting_link')->nullable();
            $table->string('room')->nullable();
            $table->string('status', 20)->default('scheduled'); // scheduled|in_progress|completed|cancelled
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'teacher_id', 'scheduled_date']);
            $table->index(['institution_id', 'class_group_id', 'scheduled_date']);
            $table->index(['start_time_utc', 'end_time_utc']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
        Schema::dropIfExists('class_schedules');
        Schema::dropIfExists('class_enrollments');
        Schema::dropIfExists('class_group_teacher');
        Schema::dropIfExists('class_groups');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('teachers');
    }
};
