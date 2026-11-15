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
        // Platform Subscription Plans table
        Schema::create('plans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('monthly_price'); // In minor units (cents), e.g. 60000 = 600.00 ETB
            $table->integer('yearly_price');  // In minor units (cents), e.g. 600000 = 6000.00 ETB
            $table->string('currency', 10)->default('ETB');
            $table->integer('student_limit')->nullable(); // null = unlimited
            $table->json('features')->nullable();
            $table->string('badge')->nullable(); // e.g. 'Most Popular', 'Recommended'
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        // Demo and Sales Inquiry Requests table
        Schema::create('demo_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('madrasa_name');
            $table->string('phone', 50);
            $table->string('email')->nullable();
            $table->string('mode', 20)->default('physical'); // online, physical, hybrid
            $table->string('student_count_range', 50)->default('50_150');
            $table->text('message')->nullable();
            $table->string('status', 20)->default('new'); // new, contacted, closed
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demo_requests');
        Schema::dropIfExists('plans');
    }
};
