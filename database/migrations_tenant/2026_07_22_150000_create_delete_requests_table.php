<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('delete_requests')) {
            return;
        }

        Schema::create('delete_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('module_key', 100)->index();
            $table->string('module_name', 150);
            $table->morphs('deletable');
            $table->string('record_label')->nullable();
            $table->json('record_snapshot')->nullable();
            $table->json('cascaded_records')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable()->index();
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedBigInteger('reviewed_by')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_remarks')->nullable();
            $table->timestamps();

            $table->index(['status', 'company_id']);
            $table->index(['deletable_type', 'deletable_id', 'status'], 'delete_requests_morph_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delete_requests');
    }
};
