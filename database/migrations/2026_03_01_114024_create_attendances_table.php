<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ref_id');
            $table->string('ref_type');
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->enum('status', ['present', 'absent', 'late', 'half day','on leave','holiday'])->default('absent');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Prevent duplicate attendance for same employee/rider on same day
            $table->unique(['ref_id', 'ref_type', 'date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance');
    }
};