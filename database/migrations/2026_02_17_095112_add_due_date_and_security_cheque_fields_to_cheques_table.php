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
        if (!Schema::hasTable('cheques')) {
            return;
        }

        Schema::table('cheques' , function (Blueprint $table) {
            // Add due_date column (nullable date)
            if (!Schema::hasColumn('cheques', 'cheque_date')) {
                $table->date('cheque_date')->nullable()->after('issue_date');
            }
            
            // Add security_cheque column with default false
            if (!Schema::hasColumn('cheques', 'is_security')) {
                $table->boolean('is_security')->default(false)->after('type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('cheques')) {
            return;
        }

        Schema::table('cheques', function (Blueprint $table) {
            $dropColumns = collect(['cheque_date', 'is_security'])
                ->filter(fn ($column) => Schema::hasColumn('cheques', $column))
                ->values()
                ->all();

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
