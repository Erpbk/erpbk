<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('rider_custom_fields')) {
            return;
        }

        Schema::table('rider_custom_fields', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('config')->constrained('rider_categories')->cascadeOnDelete();
        });

        $slugToId = DB::table('rider_categories')->whereNotNull('slug')->pluck('id', 'slug')->all();
        $defaultCategoryId = DB::table('rider_categories')->where('slug', 'other')->value('id') ?? array_values($slugToId)[0] ?? null;

        foreach (DB::table('rider_custom_fields')->get() as $row) {
            $categoryId = $slugToId[$row->category ?? ''] ?? $defaultCategoryId;
            if ($categoryId !== null) {
                DB::table('rider_custom_fields')->where('id', $row->id)->update(['category_id' => $categoryId]);
            }
        }

        Schema::table('rider_custom_fields', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('rider_custom_fields')) {
            return;
        }

        Schema::table('rider_custom_fields', function (Blueprint $table) {
            $table->string('category', 50)->nullable()->after('config');
        });

        $idToSlug = DB::table('rider_categories')->pluck('slug', 'id')->all();

        foreach (DB::table('rider_custom_fields')->get() as $row) {
            $slug = $idToSlug[$row->category_id ?? 0] ?? 'other';
            DB::table('rider_custom_fields')->where('id', $row->id)->update(['category' => $slug]);
        }

        Schema::table('rider_custom_fields', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
