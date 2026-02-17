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
        Schema::table('cheques' , function (Blueprint $table) {
            // Add due_date column (nullable date)
            $table->date('cheque_date')->nullable()->after('issue_date');
            
            // Add security_cheque column with default false
            $table->boolean('is_security')->default(false)->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cheques', function (Blueprint $table) {
            $table->dropColumn(['cheque_date','is_security']);
        });
    }
};
