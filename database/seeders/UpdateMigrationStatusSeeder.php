<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateMigrationStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if the migration already exists in the migrations table
        $migrationExists = DB::table('migrations')
            ->where('migration', '2025_10_29_000000_create_garage_items_table')
            ->exists();

        // If it doesn't exist, insert it
        if (!$migrationExists) {
            DB::table('migrations')->insert([
                'migration' => '2025_10_29_000000_create_garage_items_table',
                'batch' => DB::table('migrations')->max('batch') + 1,
            ]);

            $this->command->info('Migration 2025_10_29_000000_create_garage_items_table marked as completed.');
        } else {
            $this->command->info('Migration 2025_10_29_000000_create_garage_items_table is already marked as completed.');
        }
    }
}
