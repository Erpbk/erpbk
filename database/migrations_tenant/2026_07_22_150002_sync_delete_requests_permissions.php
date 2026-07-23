<?php

use App\Services\TenantModulePermissionsSync;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Ensure Settings → Delete Requests permissions exist for admin roles.
     */
    public function up(): void
    {
        TenantModulePermissionsSync::sync(true);
    }

    public function down(): void
    {
        // Permissions remain; intentional no-op.
    }
};
