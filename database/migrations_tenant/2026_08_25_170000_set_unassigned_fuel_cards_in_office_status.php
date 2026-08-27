<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fuel cards previously had only Active/Inactive. "Inactive" conflated two very
 * different things: a card sitting in the office ready to assign, and a card
 * taken out of service. Existing unassigned cards become "In Office"; nothing is
 * assumed to be deactivated, since that is now an explicit admin action.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fuel_cards')) {
            return;
        }

        DB::table('fuel_cards')
            ->whereNull('assigned_to')
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '')
                    ->orWhere('status', 'Inactive');
            })
            ->update(['status' => 'In Office']);

        // Assigned cards are Active by definition; repair any stragglers.
        DB::table('fuel_cards')
            ->whereNotNull('assigned_to')
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '')
                    ->orWhere('status', 'Inactive');
            })
            ->update(['status' => 'Active']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('fuel_cards')) {
            return;
        }

        DB::table('fuel_cards')
            ->whereIn('status', ['In Office', 'Deactivated'])
            ->update(['status' => 'Inactive']);
    }
};
