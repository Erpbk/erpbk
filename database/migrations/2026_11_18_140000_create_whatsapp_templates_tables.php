<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_templates')) {
            Schema::create('whatsapp_templates', function (Blueprint $table) {
                $table->id();
                $table->string('label')->nullable();
                $table->string('name');
                $table->string('language', 16)->default('en');
                $table->string('category', 32)->nullable();
                $table->string('param_style', 20)->default('positional'); // positional|named
                $table->string('status', 20)->default('active');
                $table->text('body_preview')->nullable();
                $table->timestamps();

                $table->unique(['name', 'language']);
                $table->index('status');
            });
        }

        if (! Schema::hasTable('whatsapp_template_variables')) {
            Schema::create('whatsapp_template_variables', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('whatsapp_template_id');
                $table->string('component', 20)->default('body'); // body|header|button
                $table->unsignedSmallInteger('position')->default(1);
                $table->string('parameter_name')->nullable();
                $table->string('placeholder_key');
                $table->string('label')->nullable();
                $table->string('sample_value')->nullable();
                $table->boolean('is_required')->default(true);
                $table->timestamps();

                $table->index('whatsapp_template_id');
                $table->unique(
                    ['whatsapp_template_id', 'component', 'position'],
                    'wa_tpl_var_component_position_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_template_variables');
        Schema::dropIfExists('whatsapp_templates');
    }
};
