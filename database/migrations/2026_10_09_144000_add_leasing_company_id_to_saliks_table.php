<?php

use App\Support\SafeForeignKey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('saliks')) {
            return;
        }

        Schema::table('saliks', function (Blueprint $table) {
            if (!Schema::hasColumn('saliks', 'leasing_company_id')) {
                $table->unsignedBigInteger('leasing_company_id')->nullable()->after('bike_id');
            }
        });

        if (Schema::hasTable('bikes') && Schema::hasColumn('saliks', 'leasing_company_id')) {
            DB::statement("
                UPDATE saliks s
                INNER JOIN bikes b ON b.id = s.bike_id
                SET s.leasing_company_id = b.company
                WHERE (b.bike_owner IS NULL OR LOWER(b.bike_owner) <> 'owned')
                  AND b.company IS NOT NULL
                  AND b.company <> 0
            ");
        }

        SafeForeignKey::addNullableForeignKey(
            'saliks',
            'leasing_company_id',
            'leasing_companies',
            'id',
            'saliks_leasing_company_id_fk'
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('saliks')) {
            return;
        }

        SafeForeignKey::dropForeignKeyIfExists('saliks', 'saliks_leasing_company_id_fk');

        if (Schema::hasColumn('saliks', 'leasing_company_id')) {
            Schema::table('saliks', function (Blueprint $table) {
                $table->dropColumn('leasing_company_id');
            });
        }
    }
};
