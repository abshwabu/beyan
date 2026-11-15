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
        Schema::create('user_invitations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('invited_by_id', 26)->nullable()->index();
            $table->string('email');
            $table->json('roles'); // ['Teacher'], ['Accountant', 'Teacher'], etc.
            $table->string('token', 64)->unique();
            $table->string('status')->default('pending'); // pending, accepted, expired, revoked
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('institution_id');
            $table->index(['institution_id', 'email']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_invitations');
    }
};
