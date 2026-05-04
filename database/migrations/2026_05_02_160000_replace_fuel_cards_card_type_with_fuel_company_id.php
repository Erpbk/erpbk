<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace legacy card_type with fuel_company_id -> fuel_companies.id.
     *
     * fuel_cards.company_id remains the tenant (companies.id) from shared schema;
     * fuel_company_id is the fuel supplier link (matches FuelCards::fuelCompany()).
     */
    public function up(): void
    {
        if (!Schema::hasTable('fuel_cards')) {
            return;
        }

        Schema::table('fuel_cards', function (Blueprint $table) {
            if (Schema::hasColumn('fuel_cards', 'card_type')) {
                $table->dropColumn('card_type');
            }
        });

        if (!Schema::hasTable('fuel_companies')) {
            return;
        }

        Schema::table('fuel_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('fuel_cards', 'fuel_company_id')) {
                $table->foreignId('fuel_company_id')
                    ->nullable()
                    ->after('card_number')
                    ->constrained('fuel_companies')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('fuel_cards')) {
            return;
        }

        if (Schema::hasColumn('fuel_cards', 'fuel_company_id')) {
            Schema::table('fuel_cards', function (Blueprint $table) {
                $table->dropForeign(['fuel_company_id']);
                $table->dropColumn('fuel_company_id');
            });
        }

        Schema::table('fuel_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('fuel_cards', 'card_type')) {
                $table->string('card_type')->nullable()->after('card_number');
            }
        });
    }
};
