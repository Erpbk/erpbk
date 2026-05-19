<?php

use App\Database\Migrations\EnsureCompanyIdOnAllTables;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new EnsureCompanyIdOnAllTables())->up();
    }

    public function down(): void
    {
        (new EnsureCompanyIdOnAllTables())->down();
    }
};
