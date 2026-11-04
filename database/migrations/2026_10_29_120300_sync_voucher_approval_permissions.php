<?php

use App\Services\TenantModulePermissionsSync;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        TenantModulePermissionsSync::sync(true);
    }

    public function down(): void
    {
        // Intentionally no-op: do not remove approval permissions on rollback.
    }
};
