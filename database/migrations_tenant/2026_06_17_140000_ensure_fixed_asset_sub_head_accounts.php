<?php

use App\Services\FixedAssets\AssetCategoryAccountService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('accounts')) {
            return;
        }

        app(AssetCategoryAccountService::class)->ensureSubHeadAccounts();
    }

    public function down(): void
    {
        // Intentionally no-op: sub-head accounts may be in use.
    }
};
