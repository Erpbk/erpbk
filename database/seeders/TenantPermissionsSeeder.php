<?php

namespace Database\Seeders;

use App\Services\TenantModulePermissionsSync;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class TenantPermissionsSeeder extends Seeder
{
    /**
     * Seed tenant roles/permissions used by the whole software.
     */
    public function run(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles')) {
            return;
        }

        TenantModulePermissionsSync::sync(true);

        // Keep legacy seeders for permissions that were added outside module config.
        $this->call([
            RecruiterPermissionSeeder::class,
            ActivityLogPermissionSeeder::class,
        ]);
    }
}

