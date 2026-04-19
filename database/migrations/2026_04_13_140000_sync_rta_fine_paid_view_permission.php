<?php

use App\Services\TenantModulePermissionsSync;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        TenantModulePermissionsSync::sync();
    }

    public function down(): void
    {
        // Do not remove permissions on rollback.
    }
};
