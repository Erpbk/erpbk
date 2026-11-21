<?php

use App\Services\TenantModulePermissionsSync;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Ensure WhatsApp module CRUD permissions exist on the central DB (shared by all companies).
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
