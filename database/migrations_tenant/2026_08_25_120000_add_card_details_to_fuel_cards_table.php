<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fuel_cards')) {
            return;
        }

        Schema::table('fuel_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('fuel_cards', 'service_charges')) {
                $table->decimal('service_charges', 10, 2)->nullable()->after('fuel_company_id');
            }
            if (!Schema::hasColumn('fuel_cards', 'card_issue_date')) {
                $table->date('card_issue_date')->nullable()->after('service_charges');
            }
            if (!Schema::hasColumn('fuel_cards', 'remarks')) {
                $table->text('remarks')->nullable()->after('card_issue_date');
            }
        });

        $this->addAssignedToUniqueIndex();
    }

    public function down(): void
    {
        if (!Schema::hasTable('fuel_cards')) {
            return;
        }

        Schema::table('fuel_cards', function (Blueprint $table) {
            foreach (['service_charges', 'card_issue_date', 'remarks'] as $column) {
                if (Schema::hasColumn('fuel_cards', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if ($this->hasIndex('fuel_cards_assigned_to_unique')) {
            Schema::table('fuel_cards', function (Blueprint $table) {
                $table->dropUnique('fuel_cards_assigned_to_unique');
            });
        }
    }

    /**
     * A rider may hold at most one fuel card. MySQL permits repeated NULLs in a
     * unique index, so unassigned cards are unaffected. Pre-existing duplicates
     * would abort the migration, so the index is skipped when any are present
     * and the data must be reconciled before it can be applied.
     */
    private function addAssignedToUniqueIndex(): void
    {
        if (!Schema::hasColumn('fuel_cards', 'assigned_to') || $this->hasIndex('fuel_cards_assigned_to_unique')) {
            return;
        }

        $duplicates = DB::table('fuel_cards')
            ->select('assigned_to')
            ->whereNotNull('assigned_to')
            ->groupBy('assigned_to')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('assigned_to');

        if ($duplicates->isNotEmpty()) {
            echo 'Skipped unique index on fuel_cards.assigned_to: riders holding multiple cards (ids: '
                . $duplicates->implode(', ') . "). Return the extra cards, then re-run this migration.\n";
            return;
        }

        Schema::table('fuel_cards', function (Blueprint $table) {
            $table->unique('assigned_to', 'fuel_cards_assigned_to_unique');
        });
    }

    private function hasIndex(string $index): bool
    {
        return count(DB::select("SHOW INDEX FROM `fuel_cards` WHERE Key_name = ?", [$index])) > 0;
    }
};
