<?php

use App\Support\RiderDocumentDualConsolidation;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Link legacy separately-uploaded front/back rider files to dual document types.
     * Physical files are preserved; only type/subtype metadata is updated.
     */
    public function up(): void
    {
        RiderDocumentDualConsolidation::run(false);
    }

    public function down(): void
    {
        // Irreversible data repair — files remain; metadata mapping is not undone.
    }
};
