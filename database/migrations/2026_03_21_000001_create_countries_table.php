<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shared reference data (central + tenant DBs).
     */
    public function up(): void
    {
        if (Schema::hasTable('countries')) {
            return;
        }

        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 16)->nullable()->comment('Phone country code, e.g. 971');
            $table->timestamps();
        });

        $now = now();
        $rows = [
            ['name' => 'United Arab Emirates', 'code' => '971'],
            ['name' => 'Pakistan', 'code' => '92'],
            ['name' => 'India', 'code' => '91'],
            ['name' => 'Saudi Arabia', 'code' => '966'],
            ['name' => 'United Kingdom', 'code' => '44'],
            ['name' => 'United States', 'code' => '1'],
            ['name' => 'Egypt', 'code' => '20'],
            ['name' => 'Bangladesh', 'code' => '880'],
            ['name' => 'Philippines', 'code' => '63'],
            ['name' => 'Nepal', 'code' => '977'],
            ['name' => 'Sri Lanka', 'code' => '94'],
            ['name' => 'Jordan', 'code' => '962'],
            ['name' => 'Lebanon', 'code' => '961'],
            ['name' => 'Oman', 'code' => '968'],
            ['name' => 'Kuwait', 'code' => '965'],
            ['name' => 'Qatar', 'code' => '974'],
            ['name' => 'Bahrain', 'code' => '973'],
            ['name' => 'Afghanistan', 'code' => '93'],
            ['name' => 'Iran', 'code' => '98'],
            ['name' => 'Iraq', 'code' => '964'],
            ['name' => 'Yemen', 'code' => '967'],
            ['name' => 'Sudan', 'code' => '249'],
            ['name' => 'Morocco', 'code' => '212'],
            ['name' => 'Algeria', 'code' => '213'],
            ['name' => 'Tunisia', 'code' => '216'],
            ['name' => 'Turkey', 'code' => '90'],
            ['name' => 'Germany', 'code' => '49'],
            ['name' => 'France', 'code' => '33'],
            ['name' => 'Canada', 'code' => '1'],
            ['name' => 'Australia', 'code' => '61'],
        ];

        foreach ($rows as $row) {
            DB::table('countries')->insert([
                'name' => $row['name'],
                'code' => $row['code'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
