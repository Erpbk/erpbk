<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employee_invoices')) {
            Schema::create('employee_invoices', function (Blueprint $table) {
                $table->id();
                $table->date('inv_date');
                $table->unsignedBigInteger('employee_id');
                $table->string('zone')->nullable();
                $table->string('login_hours')->nullable();
                $table->string('working_days')->nullable();
                $table->decimal('perfect_attendance', 10, 2)->nullable();
                $table->string('rejection')->nullable();
                $table->string('performance')->nullable();
                $table->string('off')->nullable();
                $table->unsignedTinyInteger('month_invoice')->nullable();
                $table->text('descriptions')->nullable();
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('vat', 12, 2)->default(0);
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->date('billing_month');
                $table->string('gaurantee')->nullable();
                $table->text('notes')->nullable();
                $table->tinyInteger('status')->default(0);
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->index(['employee_id', 'billing_month']);
            });
        }

        if (!Schema::hasTable('employee_invoice_items')) {
            Schema::create('employee_invoice_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('inv_id');
                $table->unsignedBigInteger('item_id')->nullable();
                $table->decimal('qty', 10, 2)->default(0);
                $table->decimal('rate', 12, 2)->default(0);
                $table->decimal('discount', 12, 2)->default(0);
                $table->decimal('tax', 12, 2)->default(0);
                $table->decimal('amount', 12, 2)->default(0);
                $table->timestamps();

                $table->index('inv_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_invoice_items');
        Schema::dropIfExists('employee_invoices');
    }
};

