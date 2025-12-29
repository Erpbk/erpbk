<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('banks', function (Blueprint $table) {
            // Add deleted_at column if it doesn't exist
            if (!Schema::hasColumn('banks', 'deleted_at')) {
                $table->softDeletes();
                
                // Add index for better query performance
                $table->index('deleted_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
