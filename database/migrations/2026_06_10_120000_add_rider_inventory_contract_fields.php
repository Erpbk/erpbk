<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rider_inventory_contracts')) {
            Schema::create('rider_inventory_contracts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('rider_id')->index();
                $table->enum('contract_type', ['assignment', 'return']);
                $table->string('contract_number')->index();
                $table->date('contract_date');
                $table->unsignedInteger('total_items')->default(0);
                $table->unsignedInteger('total_returned')->default(0);
                $table->unsignedInteger('total_lost')->default(0);
                $table->decimal('total_chargeable_amount', 12, 2)->default(0);
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('generated_by')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
                $table->foreign('rider_id')->references('id')->on('riders')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('rider_inventory_assignments')) {
            Schema::table('rider_inventory_assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('rider_inventory_assignments', 'assignment_contract_number')) {
                    $table->string('assignment_contract_number')->nullable()->index()->after('remarks');
                }
                if (!Schema::hasColumn('rider_inventory_assignments', 'return_contract_number')) {
                    $table->string('return_contract_number')->nullable()->index()->after('assignment_contract_number');
                }
                if (!Schema::hasColumn('rider_inventory_assignments', 'lost_by')) {
                    $table->unsignedBigInteger('lost_by')->nullable()->after('returned_by');
                }
                if (!Schema::hasColumn('rider_inventory_assignments', 'loss_date')) {
                    $table->date('loss_date')->nullable()->after('return_date');
                }
                if (!Schema::hasColumn('rider_inventory_assignments', 'il_voucher_number')) {
                    $table->string('il_voucher_number')->nullable()->index()->after('trans_code');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rider_inventory_assignments')) {
            Schema::table('rider_inventory_assignments', function (Blueprint $table) {
                foreach (['assignment_contract_number', 'return_contract_number', 'lost_by', 'loss_date', 'il_voucher_number'] as $col) {
                    if (Schema::hasColumn('rider_inventory_assignments', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        Schema::dropIfExists('rider_inventory_contracts');
    }
};
