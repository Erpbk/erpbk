<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'customer_note_label')) {
                $table->string('customer_note_label', 100)->nullable()->after('customer_note');
            }
            if (! Schema::hasColumn('customers', 'terms_and_conditions_label')) {
                $table->string('terms_and_conditions_label', 100)->nullable()->after('terms_and_conditions');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'terms_and_conditions_label')) {
                $table->dropColumn('terms_and_conditions_label');
            }
            if (Schema::hasColumn('customers', 'customer_note_label')) {
                $table->dropColumn('customer_note_label');
            }
        });
    }
};
