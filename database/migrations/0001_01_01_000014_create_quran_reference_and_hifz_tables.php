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
        // 1. Static non-tenant reference: 114 Surahs
        Schema::create('quran_surahs', function (Blueprint $table) {
            $table->unsignedSmallInteger('number')->primary();
            $table->string('name_arabic');
            $table->string('name_english');
            $table->string('name_transliteration');
            $table->unsignedSmallInteger('total_ayahs');
            $table->string('revelation_type', 10); // Meccan|Medinan
            $table->timestamps();
        });

        // 2. Static non-tenant reference: 30 Juz boundaries
        Schema::create('quran_juzs', function (Blueprint $table) {
            $table->unsignedSmallInteger('number')->primary();
            $table->string('name_arabic')->nullable();
            $table->unsignedSmallInteger('start_surah_number');
            $table->unsignedSmallInteger('start_ayah');
            $table->unsignedSmallInteger('end_surah_number');
            $table->unsignedSmallInteger('end_ayah');
            $table->timestamps();
        });

        // 3. Hifz entries (Sabaq, Sabqi, Manzil, Reading)
        Schema::create('hifz_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUlid('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->string('class_session_id', 26)->nullable()->index();
            $table->date('date');
            $table->string('type', 20); // sabaq|sabqi|manzil|reading
            $table->unsignedSmallInteger('surah_number');
            $table->unsignedSmallInteger('start_ayah');
            $table->unsignedSmallInteger('end_ayah');
            $table->string('quality_rating', 20)->default('good'); // excellent|very_good|good|acceptable|poor
            $table->string('tajweed_rating', 20)->default('good'); // excellent|good|needs_work
            $table->unsignedSmallInteger('mistakes_count')->default(0);
            $table->text('teacher_comment')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'student_id', 'date']);
            $table->index(['institution_id', 'type']);
        });

        // Enforce check constraints on hifz_entries
        DB::statement('ALTER TABLE hifz_entries ADD CONSTRAINT chk_hifz_start_ayah_positive CHECK (start_ayah >= 1)');
        DB::statement('ALTER TABLE hifz_entries ADD CONSTRAINT chk_hifz_ayah_range CHECK (start_ayah <= end_ayah)');

        // 4. Daily and weekly targets per student
        Schema::create('hifz_targets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('period', 10)->default('daily'); // daily|weekly
            $table->string('target_type', 30); // sabaq_ayahs|sabaq_pages|sabqi_pages|manzil_pages
            $table->unsignedSmallInteger('target_value');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['institution_id', 'student_id']);
        });

        // 5. Hifz completion and Ijazah certification records
        Schema::create('hifz_certifications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignUlid('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('teacher_id', 26)->nullable()->index();
            $table->string('type', 30); // hifz_completion|juz_completion|ijazah|tajweed_certificate
            $table->string('title');
            $table->string('riwayah')->nullable();
            $table->text('sanad_chain')->nullable();
            $table->date('completion_date');
            $table->string('certificate_number')->nullable();
            $table->string('grade')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hifz_certifications');
        Schema::dropIfExists('hifz_targets');
        Schema::dropIfExists('hifz_entries');
        Schema::dropIfExists('quran_juzs');
        Schema::dropIfExists('quran_surahs');
    }
};
