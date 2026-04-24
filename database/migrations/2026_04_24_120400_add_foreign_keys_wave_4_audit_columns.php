<?php

use App\Support\SafeForeignKey;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SafeForeignKey::addNullableForeignKey('riders', 'created_by', 'users', 'id', 'riders_created_by_fk');
        SafeForeignKey::addNullableForeignKey('riders', 'updated_by', 'users', 'id', 'riders_updated_by_fk');
        SafeForeignKey::addNullableForeignKey('riders', 'deleted_by', 'users', 'id', 'riders_deleted_by_fk');

        SafeForeignKey::addNullableForeignKey('bikes', 'created_by', 'users', 'id', 'bikes_created_by_fk');
        SafeForeignKey::addNullableForeignKey('bikes', 'updated_by', 'users', 'id', 'bikes_updated_by_fk');
        SafeForeignKey::addNullableForeignKey('bikes', 'deleted_by', 'users', 'id', 'bikes_deleted_by_fk');

        SafeForeignKey::addNullableForeignKey('vouchers', 'Created_By', 'users', 'id', 'vouchers_created_by_legacy_fk');
        SafeForeignKey::addNullableForeignKey('vouchers', 'Updated_By', 'users', 'id', 'vouchers_updated_by_legacy_fk');
        SafeForeignKey::addNullableForeignKey('vouchers', 'deleted_by', 'users', 'id', 'vouchers_deleted_by_fk');

        SafeForeignKey::addNullableForeignKey('transactions', 'deleted_by', 'users', 'id', 'transactions_deleted_by_fk');

        SafeForeignKey::addNullableForeignKey('rta_fines', 'deleted_by', 'users', 'id', 'rta_fines_deleted_by_fk');
        SafeForeignKey::addNullableForeignKey('supplier_invoices', 'created_by', 'users', 'id', 'supplier_invoices_created_by_fk');
        SafeForeignKey::addNullableForeignKey('supplier_invoices', 'updated_by', 'users', 'id', 'supplier_invoices_updated_by_fk');
    }

    public function down(): void
    {
        SafeForeignKey::dropForeignKeyIfExists('supplier_invoices', 'supplier_invoices_updated_by_fk');
        SafeForeignKey::dropForeignKeyIfExists('supplier_invoices', 'supplier_invoices_created_by_fk');
        SafeForeignKey::dropForeignKeyIfExists('rta_fines', 'rta_fines_deleted_by_fk');
        SafeForeignKey::dropForeignKeyIfExists('transactions', 'transactions_deleted_by_fk');

        SafeForeignKey::dropForeignKeyIfExists('vouchers', 'vouchers_deleted_by_fk');
        SafeForeignKey::dropForeignKeyIfExists('vouchers', 'vouchers_updated_by_legacy_fk');
        SafeForeignKey::dropForeignKeyIfExists('vouchers', 'vouchers_created_by_legacy_fk');

        SafeForeignKey::dropForeignKeyIfExists('bikes', 'bikes_deleted_by_fk');
        SafeForeignKey::dropForeignKeyIfExists('bikes', 'bikes_updated_by_fk');
        SafeForeignKey::dropForeignKeyIfExists('bikes', 'bikes_created_by_fk');

        SafeForeignKey::dropForeignKeyIfExists('riders', 'riders_deleted_by_fk');
        SafeForeignKey::dropForeignKeyIfExists('riders', 'riders_updated_by_fk');
        SafeForeignKey::dropForeignKeyIfExists('riders', 'riders_created_by_fk');
    }
};
