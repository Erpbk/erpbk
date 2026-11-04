<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('voucher_approval_events')) {
            return;
        }

        Schema::create('voucher_approval_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->morphs('approvable');
            $table->string('action', 64)->index();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name')->nullable();
            $table->text('remark')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['approvable_type', 'approvable_id', 'created_at'], 'vae_approvable_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_approval_events');
    }
};
