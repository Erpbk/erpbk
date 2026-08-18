<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bike_rent_companies')) {
            Schema::table('bike_rent_companies', function (Blueprint $table) {
                if (! Schema::hasColumn('bike_rent_companies', 'party_type')) {
                    $table->string('party_type')->default('company')->after('customer_type');
                    $table->index('party_type');
                }
                if (! Schema::hasColumn('bike_rent_companies', 'emirates_id')) {
                    $table->string('emirates_id')->nullable()->after('address');
                }
                if (! Schema::hasColumn('bike_rent_companies', 'emirates_expiry')) {
                    $table->date('emirates_expiry')->nullable()->after('emirates_id');
                }
                if (! Schema::hasColumn('bike_rent_companies', 'passport_no')) {
                    $table->string('passport_no')->nullable()->after('emirates_expiry');
                }
                if (! Schema::hasColumn('bike_rent_companies', 'passport_expiry')) {
                    $table->date('passport_expiry')->nullable()->after('passport_no');
                }
                if (! Schema::hasColumn('bike_rent_companies', 'dob')) {
                    $table->date('dob')->nullable()->after('passport_expiry');
                }
                if (! Schema::hasColumn('bike_rent_companies', 'nationality')) {
                    $table->string('nationality')->nullable()->after('dob');
                }
                if (! Schema::hasColumn('bike_rent_companies', 'license_no')) {
                    $table->string('license_no')->nullable()->after('nationality');
                }
                if (! Schema::hasColumn('bike_rent_companies', 'license_expiry')) {
                    $table->date('license_expiry')->nullable()->after('license_no');
                }
            });
        }

        if (Schema::hasTable('bike_assign_field_assignments')) {
            $assignType = DB::table('bike_assign_field_assignments')->where('field_key', 'assign_type')->first();
            if ($assignType) {
                $config = [];
                if (! empty($assignType->input_config)) {
                    $decoded = json_decode($assignType->input_config, true);
                    $config = is_array($decoded) ? $decoded : [];
                }
                $config['assign_options'] = [
                    'rider' => 'Rider',
                    'rental' => 'Rental customer',
                    'garage' => 'Garage customer',
                ];
                DB::table('bike_assign_field_assignments')
                    ->where('id', $assignType->id)
                    ->update(['input_config' => json_encode($config)]);
            }

            DB::table('bike_assign_field_assignments')
                ->where('field_key', 'rental_company_id')
                ->where(function ($q) {
                    $q->whereNull('display_label')->orWhere('display_label', 'Company');
                })
                ->update(['display_label' => 'Rental customer']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bike_rent_companies')) {
            Schema::table('bike_rent_companies', function (Blueprint $table) {
                $columns = [
                    'party_type',
                    'emirates_id',
                    'emirates_expiry',
                    'passport_no',
                    'passport_expiry',
                    'dob',
                    'nationality',
                    'license_no',
                    'license_expiry',
                ];
                $drop = array_values(array_filter($columns, fn ($col) => Schema::hasColumn('bike_rent_companies', $col)));
                if ($drop !== []) {
                    $table->dropColumn($drop);
                }
            });
        }

        if (Schema::hasTable('bike_assign_field_assignments')) {
            $assignType = DB::table('bike_assign_field_assignments')->where('field_key', 'assign_type')->first();
            if ($assignType) {
                $config = [];
                if (! empty($assignType->input_config)) {
                    $decoded = json_decode($assignType->input_config, true);
                    $config = is_array($decoded) ? $decoded : [];
                }
                $config['assign_options'] = [
                    'rider' => 'Rider',
                    'company' => 'Company',
                ];
                DB::table('bike_assign_field_assignments')
                    ->where('id', $assignType->id)
                    ->update(['input_config' => json_encode($config)]);
            }

            DB::table('bike_assign_field_assignments')
                ->where('field_key', 'rental_company_id')
                ->where('display_label', 'Rental customer')
                ->update(['display_label' => 'Company']);
        }
    }
};
