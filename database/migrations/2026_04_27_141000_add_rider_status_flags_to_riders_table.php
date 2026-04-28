<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('riders')) {
            return;
        }

        Schema::table('riders', function (Blueprint $table) {
            if (!Schema::hasColumn('riders', 'absconder')) {
                $table->boolean('absconder')->default(false)->after('status');
            }
            if (!Schema::hasColumn('riders', 'flowup')) {
                $table->boolean('flowup')->default(false)->after('absconder');
            }
            if (!Schema::hasColumn('riders', 'l_license')) {
                $table->boolean('l_license')->default(false)->after('flowup');
            }
            if (!Schema::hasColumn('riders', 'walker')) {
                $table->boolean('walker')->default(false)->after('l_license');
            }
            if (!Schema::hasColumn('riders', 'vacation')) {
                $table->boolean('vacation')->default(false)->after('walker');
            }
            if (!Schema::hasColumn('riders', 'cancel')) {
                $table->boolean('cancel')->default(false)->after('vacation');
            }
            if (!Schema::hasColumn('riders', 'pro')) {
                $table->boolean('pro')->default(false)->after('cancel');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('riders')) {
            return;
        }

        Schema::table('riders', function (Blueprint $table) {
            $drop = [];
            foreach (['absconder', 'flowup', 'l_license', 'walker', 'vacation', 'cancel', 'pro'] as $column) {
                if (Schema::hasColumn('riders', $column)) {
                    $drop[] = $column;
                }
            }
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};

