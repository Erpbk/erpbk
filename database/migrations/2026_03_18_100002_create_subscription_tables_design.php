<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Future-ready subscription design only. Do not implement logic yet.
     * Central database.
     */
    public function up(): void
    {
        // Subscription plans (e.g. Basic, Pro, Enterprise)
        if (!Schema::hasTable('subscription_plans')) {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('billing_interval', 20)->nullable(); // monthly, yearly
            $table->integer('trial_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
        }

        // Plan features (what each plan includes)
        if (!Schema::hasTable('plan_features')) {
        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->string('feature_key'); // e.g. 'max_users', 'vat_returns'
            $table->string('feature_name');
            $table->string('value')->nullable(); // limit or flag
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
        }

        // Company subscriptions (which plan a company is on, expiry, status)
        if (!Schema::hasTable('company_subscriptions')) {
        Schema::create('company_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->enum('status', ['trial', 'active', 'expired', 'cancelled'])->default('trial');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_subscriptions');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('subscription_plans');
    }
};
