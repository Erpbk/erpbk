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
        Schema::table('supplier_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('supplier_invoices', 'subtotal')) {
                $table->decimal('subtotal', 10, 2, true);
            }
            if (! Schema::hasColumn('supplier_invoices', 'vat')) {
                $table->decimal('vat', 8, 2, true);
            }
            if (! Schema::hasColumn('supplier_invoices', 'partial_paid_amount')) {
                $table->string('partial_paid_amount')->nullable();
            }
            if (! Schema::hasColumn('supplier_invoices', 'status')) {
                $table->string('status')->default('unpaid');
            }
            if (! Schema::hasColumn('supplier_invoices', 'is_order')) {
                $table->boolean('is_order')->default(false);
            }
            if (! Schema::hasColumn('supplier_invoices', 'is_invoice')) {
                $table->boolean('is_invoice')->default(false);
            }
            if (! Schema::hasColumn('supplier_invoices', 'attachment')) {
                $table->string('attachment')->nullable();
            }
            if (! Schema::hasColumn('supplier_invoices', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
            }
            if (! Schema::hasColumn('supplier_invoices', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable();
            }
            if (! Schema::hasColumn('supplier_invoices', 'order_date')) {
                $table->date('order_date')->nullable();
            }

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_invoices', function (Blueprint $table) {
            $dropColumns = [];
            foreach ([
                'subtotal',
                'vat',
                'partial_paid_amount',
                'status',
                'is_order',
                'is_invoice',
                'attachment',
                'created_by',
                'updated_by',
                'order_date',
            ] as $column) {
                if (Schema::hasColumn('supplier_invoices', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if (! empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
