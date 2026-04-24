<?php

use App\Support\SafeForeignKey;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SafeForeignKey::addNullableForeignKey('rider_invoices', 'rider_id', 'riders', 'id', 'rider_invoices_rider_id_fk');
        SafeForeignKey::addNullableForeignKey('rider_invoices', 'vendor_id', 'vendors', 'id', 'rider_invoices_vendor_id_fk');
        SafeForeignKey::addNullableForeignKey('rider_invoice_items', 'inv_id', 'rider_invoices', 'id', 'rider_invoice_items_inv_id_fk');
        SafeForeignKey::addNullableForeignKey('rider_invoice_items', 'item_id', 'items', 'id', 'rider_invoice_items_item_id_fk');

        SafeForeignKey::addNullableForeignKey('supplier_invoices', 'supplier_id', 'suppliers', 'id', 'supplier_invoices_supplier_id_fk');
        SafeForeignKey::addNullableForeignKey('supplier_invoice_items', 'inv_id', 'supplier_invoices', 'id', 'supplier_invoice_items_inv_id_fk');
        SafeForeignKey::addNullableForeignKey('supplier_invoice_items', 'item_id', 'items', 'id', 'supplier_invoice_items_item_id_fk');

        SafeForeignKey::addNullableForeignKey('payments', 'bank_id', 'banks', 'id', 'payments_bank_id_fk', 'restrict');
        SafeForeignKey::addNullableForeignKey('payments', 'voucher_id', 'vouchers', 'id', 'payments_voucher_id_fk');

        SafeForeignKey::addNullableForeignKey('receipts', 'account_id', 'accounts', 'id', 'receipts_account_id_fk');
        SafeForeignKey::addNullableForeignKey('receipts', 'bank_id', 'banks', 'id', 'receipts_bank_id_fk');
        SafeForeignKey::addNullableForeignKey('receipts', 'leasing_company_id', 'leasing_companies', 'id', 'receipts_leasing_company_id_fk');
        SafeForeignKey::addNullableForeignKey('receipts', 'voucher_id', 'vouchers', 'id', 'receipts_voucher_id_fk');

        SafeForeignKey::addNullableForeignKey('cheques', 'bank_id', 'banks', 'id', 'cheques_bank_id_fk', 'restrict');
        SafeForeignKey::addNullableForeignKey('cheques', 'voucher_id', 'vouchers', 'id', 'cheques_voucher_id_fk');
    }

    public function down(): void
    {
        SafeForeignKey::dropForeignKeyIfExists('cheques', 'cheques_voucher_id_fk');
        SafeForeignKey::dropForeignKeyIfExists('cheques', 'cheques_bank_id_fk');

        SafeForeignKey::dropForeignKeyIfExists('receipts', 'receipts_voucher_id_fk');
        SafeForeignKey::dropForeignKeyIfExists('receipts', 'receipts_leasing_company_id_fk');
        SafeForeignKey::dropForeignKeyIfExists('receipts', 'receipts_bank_id_fk');
        SafeForeignKey::dropForeignKeyIfExists('receipts', 'receipts_account_id_fk');

        SafeForeignKey::dropForeignKeyIfExists('payments', 'payments_voucher_id_fk');
        SafeForeignKey::dropForeignKeyIfExists('payments', 'payments_bank_id_fk');

        SafeForeignKey::dropForeignKeyIfExists('supplier_invoice_items', 'supplier_invoice_items_item_id_fk');
        SafeForeignKey::dropForeignKeyIfExists('supplier_invoice_items', 'supplier_invoice_items_inv_id_fk');
        SafeForeignKey::dropForeignKeyIfExists('supplier_invoices', 'supplier_invoices_supplier_id_fk');

        SafeForeignKey::dropForeignKeyIfExists('rider_invoice_items', 'rider_invoice_items_item_id_fk');
        SafeForeignKey::dropForeignKeyIfExists('rider_invoice_items', 'rider_invoice_items_inv_id_fk');
        SafeForeignKey::dropForeignKeyIfExists('rider_invoices', 'rider_invoices_vendor_id_fk');
        SafeForeignKey::dropForeignKeyIfExists('rider_invoices', 'rider_invoices_rider_id_fk');
    }
};
