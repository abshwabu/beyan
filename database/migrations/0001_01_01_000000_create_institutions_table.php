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
        Schema::create('institutions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->json('contact')->nullable();
            $table->string('mode')->default('hybrid'); // online, physical, hybrid
            $table->string('timezone')->default('Africa/Addis_Ababa');
            $table->string('currency', 10)->default('ETB');
            $table->string('locale', 10)->default('en'); // en, am, om, ar
            $table->string('status')->default('active'); // active, suspended, inactive
            $table->string('subscription_plan')->nullable()->default('pilot');
            $table->timestamp('subscription_ends_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};
