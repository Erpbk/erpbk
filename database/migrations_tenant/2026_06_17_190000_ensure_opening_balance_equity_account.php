<?php

use App\Services\FixedAssets\OpeningBalanceAccountService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('accounts')) {
            return;
        }

        app(OpeningBalanceAccountService::class)->ensureOpeningBalanceEquityAccount();
    }

    public function down(): void
    {
        // Intentionally no-op: shared accounts may be in use.
    }
};
