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
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->string('class_session_id', 26)->nullable()->index();
            $table->string('session_id', 26)->nullable()->index();
            $table->text('note')->nullable();
            $table->dateTime('recorded_at')->nullable();

            $table->unique(['session_id', 'student_id']);
            $table->unique(['class_session_id', 'student_id']);
        });

        Schema::table('class_sessions', function (Blueprint $table) {
            $table->string('teacher_attendance_status', 20)->nullable();
            $table->dateTime('teacher_checked_in_at')->nullable();
            $table->text('teacher_check_in_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'teacher_attendance_status',
                'teacher_checked_in_at',
                'teacher_check_in_notes',
            ]);
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropUnique(['session_id', 'student_id']);
            $table->dropUnique(['class_session_id', 'student_id']);
            $table->dropColumn([
                'class_session_id',
                'session_id',
                'note',
                'recorded_at',
            ]);
        });
    }
};
