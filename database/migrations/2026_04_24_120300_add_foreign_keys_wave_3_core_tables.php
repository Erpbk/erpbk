<?php

use App\Support\SafeForeignKey;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SafeForeignKey::addNullableForeignKey('riders', 'branch_id', 'branches', 'id', 'riders_branch_id_fk');
        SafeForeignKey::addNullableForeignKey('riders', 'account_id', 'accounts', 'id', 'riders_account_id_fk');
        SafeForeignKey::addNullableForeignKey('riders', 'nationality', 'countries', 'id', 'riders_nationality_fk');
        SafeForeignKey::addNullableForeignKey('riders', 'recruiter_id', 'recruiters', 'id', 'riders_recruiter_id_fk');

        SafeForeignKey::addNullableForeignKey('bikes', 'rider_id', 'riders', 'id', 'bikes_rider_id_fk');
        SafeForeignKey::addNullableForeignKey('bikes', 'branch_id', 'branches', 'id', 'bikes_branch_id_fk');

        SafeForeignKey::addNullableForeignKey('transactions', 'account_id', 'accounts', 'id', 'transactions_account_id_fk_additive');
        SafeForeignKey::addNullableForeignKey('transactions', 'branch_id', 'branches', 'id', 'transactions_branch_id_fk');

        SafeForeignKey::addNullableForeignKey('vouchers', 'branch_id', 'branches', 'id', 'vouchers_branch_id_fk');
        SafeForeignKey::addNullableForeignKey('vouchers', 'rider_id', 'riders', 'id', 'vouchers_rider_id_fk');
        SafeForeignKey::addNullableForeignKey('vouchers', 'vendor_id', 'vendors', 'id', 'vouchers_vendor_id_fk');
        SafeForeignKey::addNullableForeignKey('vouchers', 'lease_company', 'leasing_companies', 'id', 'vouchers_lease_company_fk');

        SafeForeignKey::addNullableForeignKey('rta_fines', 'rider_id', 'riders', 'id', 'rta_fines_rider_id_fk_additive');
        SafeForeignKey::addNullableForeignKey('rta_fines', 'bike_id', 'bikes', 'id', 'rta_fines_bike_id_fk_additive');
        SafeForeignKey::addNullableForeignKey('rta_fines', 'debit_account_id', 'accounts', 'id', 'rta_fines_debit_account_id_fk');
        SafeForeignKey::addNullableForeignKey('rta_fines', 'branch_id', 'branches', 'id', 'rta_fines_branch_id_fk');

        SafeForeignKey::addNullableForeignKey('ledger_entries', 'account_id', 'accounts', 'id', 'ledger_entries_account_id_fk_additive');
    }

    public function down(): void
    {
        SafeForeignKey::dropForeignKeyIfExists('ledger_entries', 'ledger_entries_account_id_fk_additive');

        SafeForeignKey::dropForeignKeyIfExists('rta_fines', 'rta_fines_branch_id_fk');
        SafeForeignKey::dropForeignKeyIfExists('rta_fines', 'rta_fines_debit_account_id_fk');
        SafeForeignKey::dropForeignKeyIfExists('rta_fines', 'rta_fines_bike_id_fk_additive');
        SafeForeignKey::dropForeignKeyIfExists('rta_fines', 'rta_fines_rider_id_fk_additive');

        SafeForeignKey::dropForeignKeyIfExists('vouchers', 'vouchers_lease_company_fk');
        SafeForeignKey::dropForeignKeyIfExists('vouchers', 'vouchers_vendor_id_fk');
        SafeForeignKey::dropForeignKeyIfExists('vouchers', 'vouchers_rider_id_fk');
        SafeForeignKey::dropForeignKeyIfExists('vouchers', 'vouchers_branch_id_fk');

        SafeForeignKey::dropForeignKeyIfExists('transactions', 'transactions_branch_id_fk');
        SafeForeignKey::dropForeignKeyIfExists('transactions', 'transactions_account_id_fk_additive');

        SafeForeignKey::dropForeignKeyIfExists('bikes', 'bikes_branch_id_fk');
        SafeForeignKey::dropForeignKeyIfExists('bikes', 'bikes_rider_id_fk');

        SafeForeignKey::dropForeignKeyIfExists('riders', 'riders_recruiter_id_fk');
        SafeForeignKey::dropForeignKeyIfExists('riders', 'riders_nationality_fk');
        SafeForeignKey::dropForeignKeyIfExists('riders', 'riders_account_id_fk');
        SafeForeignKey::dropForeignKeyIfExists('riders', 'riders_branch_id_fk');
    }
};
