<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agreement_letterheads')) {
            Schema::create('agreement_letterheads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('name');
                $table->string('path', 500);
                $table->string('original_name')->nullable();
                $table->json('suggested_margins')->nullable();
                $table->timestamps();

                $table->index('company_id');
            });
        }

        if (Schema::hasTable('agreement_categories') && ! Schema::hasColumn('agreement_categories', 'letterhead_id')) {
            Schema::table('agreement_categories', function (Blueprint $table) {
                $table->unsignedBigInteger('letterhead_id')->nullable()->after('letterhead_path');
            });
        }

        $this->migrateExistingLetterheads();

        if (Schema::hasTable('agreement_categories') && Schema::hasColumn('agreement_categories', 'letterhead_id')) {
            Schema::table('agreement_categories', function (Blueprint $table) {
                $table->foreign('letterhead_id')
                    ->references('id')
                    ->on('agreement_letterheads')
                    ->nullOnDelete();
            });
        }
    }

    private function migrateExistingLetterheads(): void
    {
        if (! Schema::hasTable('agreement_categories') || ! Schema::hasColumn('agreement_categories', 'letterhead_path')) {
            return;
        }

        $rows = DB::table('agreement_categories')
            ->whereNotNull('letterhead_path')
            ->where('letterhead_path', '!=', '')
            ->orderBy('id')
            ->get(['id', 'company_id', 'name', 'letterhead_path', 'letterhead_margins']);

        $byKey = [];

        foreach ($rows as $row) {
            $path = ltrim((string) $row->letterhead_path, '/');
            if ($path === '') {
                continue;
            }

            $key = (int) $row->company_id . ':' . $path;
            if (! isset($byKey[$key])) {
                $margins = $row->letterhead_margins;
                if (is_string($margins) && $margins !== '') {
                    $decoded = json_decode($margins, true);
                    $margins = is_array($decoded) ? $decoded : null;
                }

                $id = DB::table('agreement_letterheads')->insertGetId([
                    'company_id' => $row->company_id,
                    'name' => trim((string) $row->name) !== '' ? $row->name : 'Letterhead',
                    'path' => $path,
                    'original_name' => null,
                    'suggested_margins' => is_array($margins) ? json_encode($margins) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $byKey[$key] = $id;
            }

            DB::table('agreement_categories')
                ->where('id', $row->id)
                ->update(['letterhead_id' => $byKey[$key]]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('agreement_categories') && Schema::hasColumn('agreement_categories', 'letterhead_id')) {
            Schema::table('agreement_categories', function (Blueprint $table) {
                $table->dropForeign(['letterhead_id']);
                $table->dropColumn('letterhead_id');
            });
        }

        Schema::dropIfExists('agreement_letterheads');
    }
};
