<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cheque_document_types')) {
            Schema::create('cheque_document_types', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->string('key', 80);
                $table->string('label', 255)->nullable();
                $table->string('type', 20)->default('single');
                $table->string('front_label', 255)->nullable();
                $table->string('back_label', 255)->nullable();
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['company_id', 'key'], 'cheque_document_types_company_key_unique');
            });
        }

        if (Schema::hasTable('cheque_document_types') && DB::table('cheque_document_types')->count() === 0) {
            $defaults = [
                ['key' => 'attachment', 'label' => 'Cheque Attachment', 'type' => 'single', 'display_order' => 0],
            ];
            $now = now();
            foreach ($defaults as $row) {
                DB::table('cheque_document_types')->insert(array_merge($row, [
                    'front_label' => null,
                    'back_label' => null,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cheque_document_types');
    }
};
