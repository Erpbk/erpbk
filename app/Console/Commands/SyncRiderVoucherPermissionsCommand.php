<?php

namespace App\Console\Commands;

use App\Services\TenantModulePermissionsSync;
use App\Support\RiderVoucherPermissions;
use App\Support\VoucherModulePermissions;
use Illuminate\Console\Command;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rebuilds Riders voucher-type permission leaves (COD / Penalty / … / Upload)
 * and remaps legacy riders_voucher_* grants. Also ensures Vouchers module
 * Upload leaf (journal / funds transfer). Safe to re-run after deploy.
 */
class SyncRiderVoucherPermissionsCommand extends Command
{
    protected $signature = 'permissions:sync-rider-vouchers
                            {--force : Required in production}';

    protected $description = 'Sync per-type Rider voucher permissions (CRUD + upload) and Vouchers upload from config.';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Refusing to run in production without --force.');

            return self::FAILURE;
        }

        $this->info('Capturing legacy riders_voucher_* / payments / vouchers upload grants…');
        $map = array_merge(
            RiderVoucherPermissions::legacyGrantMigrationMap(),
            VoucherModulePermissions::uploadGrantMigrationMap()
        );
        $captured = TenantModulePermissionsSync::capturePermissionAssignments($map);

        $this->info('Syncing module permission tree from config…');
        TenantModulePermissionsSync::sync(true);

        $this->info('Re-applying captured grants onto typed voucher / vouchers_upload leaves…');
        TenantModulePermissionsSync::applyCapturedPermissionAssignments($captured);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->info('Permission cache cleared.');

        $this->info('Done. Refresh Role Permissions — Riders typed vouchers + Vouchers Upload (journal document attach).');

        return self::SUCCESS;
    }
}
