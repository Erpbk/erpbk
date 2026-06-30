<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('global_accounts')) {
            return;
        }

        Schema::create('global_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('label', 150);
            $table->text('description')->nullable();
            $table->bigInteger('account_id')->nullable();
            $table->string('account_type', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('account_id')
                ->references('id')
                ->on('accounts')
                ->nullOnDelete()
                ->restrictOnUpdate();

            $table->unique('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_accounts');
    }
};
