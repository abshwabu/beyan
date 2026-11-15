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
        // 1. Add user_limit to plans table if not exists
        if (Schema::hasTable('plans') && ! Schema::hasColumn('plans', 'user_limit')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->integer('user_limit')->nullable()->after('student_limit');
            });
        }

        // 2. Add country and soft deletes to institutions table
        if (Schema::hasTable('institutions')) {
            Schema::table('institutions', function (Blueprint $table) {
                if (! Schema::hasColumn('institutions', 'country')) {
                    $table->string('country', 10)->default('ET')->after('locale');
                }
                if (! Schema::hasColumn('institutions', 'deleted_at')) {
                    $table->softDeletes()->after('updated_at');
                }
            });
        }

        // 3. Add notes to demo_requests table
        if (Schema::hasTable('demo_requests') && ! Schema::hasColumn('demo_requests', 'notes')) {
            Schema::table('demo_requests', function (Blueprint $table) {
                $table->text('notes')->nullable()->after('message');
            });
        }

        // 4. Subscriptions table
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('institution_id');
            $table->ulid('plan_id')->nullable();
            $table->string('billing_cycle', 20)->default('monthly'); // monthly, yearly
            $table->string('status', 30)->default('trial'); // trial, active, past_due, grace_period, expired, cancelled
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->integer('grace_days')->default(7);
            $table->json('reminder_history')->nullable(); // Track 7d, 3d, 1d reminders sent
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('institution_id')->references('id')->on('institutions')->cascadeOnDelete();
            $table->foreign('plan_id')->references('id')->on('plans')->nullOnDelete();

            $table->index(['institution_id', 'status']);
            $table->index(['status', 'ends_at']);
        });

        // 5. Platform Payments (Manual Ethiopia Cash/Bank Transfer/Mobile Money)
        Schema::create('platform_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('institution_id');
            $table->ulid('subscription_id')->nullable();
            $table->integer('amount'); // Minor units (e.g. 60000 = 600.00 ETB)
            $table->string('currency', 10)->default('ETB');
            $table->string('method', 30)->default('bank_transfer'); // cash, bank_transfer, mobile_money, other
            $table->string('reference')->nullable(); // CBE / Telebirr transaction code
            $table->timestamp('paid_at');
            $table->string('recorded_by');
            $table->text('note')->nullable();
            $table->string('proof_path')->nullable(); // Private storage path
            $table->string('status', 20)->default('completed'); // completed, reversed
            $table->text('reversal_reason')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->string('reversed_by')->nullable();
            $table->string('idempotency_key')->unique();
            $table->timestamps();

            $table->foreign('institution_id')->references('id')->on('institutions')->cascadeOnDelete();
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->nullOnDelete();

            $table->index(['institution_id', 'status']);
            $table->index('paid_at');
        });

        // 6. Platform Announcements (System-wide banners for institutions)
        Schema::create('platform_announcements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->text('body');
            $table->string('target_mode', 20)->nullable(); // physical, online, hybrid, all/null
            $table->string('target_plan', 50)->nullable(); // starter, growth, institute, all/null
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('is_active')->default(true);
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        // 7. Platform Announcement Dismissals
        Schema::create('platform_announcement_dismissals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('announcement_id');
            $table->ulid('user_id');
            $table->timestamp('dismissed_at');
            $table->timestamps();

            $table->foreign('announcement_id')->references('id')->on('platform_announcements')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->unique(['announcement_id', 'user_id']);
        });

        // 8. Platform System Settings (Key-Value)
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('key')->unique();
            $table->json('value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('platform_announcement_dismissals');
        Schema::dropIfExists('platform_announcements');
        Schema::dropIfExists('platform_payments');
        Schema::dropIfExists('subscriptions');

        if (Schema::hasTable('demo_requests') && Schema::hasColumn('demo_requests', 'notes')) {
            Schema::table('demo_requests', function (Blueprint $table) {
                $table->dropColumn('notes');
            });
        }

        if (Schema::hasTable('institutions')) {
            Schema::table('institutions', function (Blueprint $table) {
                if (Schema::hasColumn('institutions', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
                if (Schema::hasColumn('institutions', 'country')) {
                    $table->dropColumn('country');
                }
            });
        }

        if (Schema::hasTable('plans') && Schema::hasColumn('plans', 'user_limit')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->dropColumn('user_limit');
            });
        }
    }
};
