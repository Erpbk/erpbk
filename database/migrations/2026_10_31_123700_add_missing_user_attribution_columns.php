<?php

use App\Support\SafeForeignKey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Soft-deleting / show-backed tables that need audit user columns.
     * Vouchers already use Created_By / Updated_By — do not rename those.
     *
     * @var array<int, array{table: string, soft_deletes: bool}>
     */
    private array $tables = [
        ['table' => 'banks', 'soft_deletes' => true],
        ['table' => 'customers', 'soft_deletes' => true],
        ['table' => 'suppliers', 'soft_deletes' => true],
        ['table' => 'leasing_companies', 'soft_deletes' => true],
        ['table' => 'fuel_data', 'soft_deletes' => true],
        ['table' => 'rta_fines', 'soft_deletes' => true],
        ['table' => 'customer_invoices', 'soft_deletes' => true],
        ['table' => 'customer_invoice_items', 'soft_deletes' => true],
        ['table' => 'transactions', 'soft_deletes' => true],
        ['table' => 'employee_invoices', 'soft_deletes' => true],
        ['table' => 'sim_invoices', 'soft_deletes' => true],
        ['table' => 'visa_expenses', 'soft_deletes' => true],
        ['table' => 'license_expenses', 'soft_deletes' => true],
        ['table' => 'legal_cases', 'soft_deletes' => true],
        ['table' => 'cheques', 'soft_deletes' => true],
        ['table' => 'branches', 'soft_deletes' => true],
        ['table' => 'rider_invoices', 'soft_deletes' => true],
        ['table' => 'loans', 'soft_deletes' => true],
        ['table' => 'loan_installments', 'soft_deletes' => true],
        ['table' => 'bank_accounts', 'soft_deletes' => true],
        ['table' => 'bike_registrations', 'soft_deletes' => true],
        ['table' => 'employees', 'soft_deletes' => true],
        ['table' => 'bike_maintenances', 'soft_deletes' => true],
        ['table' => 'bike_maintenance_items', 'soft_deletes' => true],
        ['table' => 'leasing_company_billing_invoices', 'soft_deletes' => true],
        ['table' => 'leasing_company_invoices', 'soft_deletes' => true],
        ['table' => 'inventory_purchases', 'soft_deletes' => true],
        ['table' => 'rider_item_prices', 'soft_deletes' => true],
        ['table' => 'visa_statuses', 'soft_deletes' => true],
        ['table' => 'license_statuses', 'soft_deletes' => true],
        ['table' => 'legal_case_statuses', 'soft_deletes' => true],
        ['table' => 'visa_renewal_categories', 'soft_deletes' => true],
        ['table' => 'fuel_cards', 'soft_deletes' => true],
        ['table' => 'accounts', 'soft_deletes' => true],
    ];

    public function up(): void
    {
        foreach ($this->tables as $meta) {
            $table = $meta['table'];
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $meta): void {
                if (! Schema::hasColumn($table, 'created_by')) {
                    $blueprint->unsignedBigInteger('created_by')->nullable();
                }
                if (! Schema::hasColumn($table, 'updated_by')) {
                    $blueprint->unsignedBigInteger('updated_by')->nullable();
                }
                if ($meta['soft_deletes'] && ! Schema::hasColumn($table, 'deleted_by')) {
                    if (Schema::hasColumn($table, 'deleted_at')) {
                        $blueprint->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
                    } else {
                        $blueprint->unsignedBigInteger('deleted_by')->nullable();
                    }
                }
            });

            SafeForeignKey::addNullableForeignKey($table, 'created_by', 'users', 'id', "{$table}_created_by_attr_fk");
            SafeForeignKey::addNullableForeignKey($table, 'updated_by', 'users', 'id', "{$table}_updated_by_attr_fk");
            if ($meta['soft_deletes']) {
                SafeForeignKey::addNullableForeignKey($table, 'deleted_by', 'users', 'id', "{$table}_deleted_by_attr_fk");
            }
        }
    }

    public function down(): void
    {
        // Drop only FKs added by this migration. Leave columns in place so shared
        // databases do not lose historically present audit data on rollback.
        foreach (array_reverse($this->tables) as $meta) {
            $table = $meta['table'];
            if (! Schema::hasTable($table)) {
                continue;
            }

            SafeForeignKey::dropForeignKeyIfExists($table, "{$table}_deleted_by_attr_fk");
            SafeForeignKey::dropForeignKeyIfExists($table, "{$table}_updated_by_attr_fk");
            SafeForeignKey::dropForeignKeyIfExists($table, "{$table}_created_by_attr_fk");
        }
    }
};
