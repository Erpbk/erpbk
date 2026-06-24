<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rider_invoice_templates')) {
            Schema::create('rider_invoice_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->string('template_name');
                $table->string('layout_key', 50)->default('modern');
                $table->text('description')->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('status')->default(true);
                $table->unsignedInteger('display_order')->default(1);
                $table->timestamps();

                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('rider_invoices') && ! Schema::hasColumn('rider_invoices', 'template_id')) {
            Schema::table('rider_invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('template_id')->nullable()->after('branch_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rider_invoices') && Schema::hasColumn('rider_invoices', 'template_id')) {
            Schema::table('rider_invoices', function (Blueprint $table) {
                $table->dropColumn('template_id');
            });
        }

        Schema::dropIfExists('rider_invoice_templates');
    }
};
