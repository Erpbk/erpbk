<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rider_invoice_account_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('module_key', 80)->default('invoices')->index();
            $table->enum('side', ['debit', 'credit'])->index();
            $table->unsignedBigInteger('parent_account_id');
            $table->unsignedBigInteger('child_account_id');
            $table->timestamps();

            $table->unique(
                ['company_id', 'module_key', 'side', 'parent_account_id', 'child_account_id'],
                'riaa_company_module_side_parent_child_unique'
            );
            $table->index(
                ['company_id', 'module_key', 'side'],
                'riaa_company_module_side_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_invoice_account_assignments');
    }
};
