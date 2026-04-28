<?php

use App\Support\SafeForeignKey;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SafeForeignKey::addNullableForeignKey('user_email_settings', 'user_id', 'users', 'id', 'ues_user_id_fk');
        SafeForeignKey::addNullableForeignKey('user_email_cc_recipients', 'user_id', 'users', 'id', 'uecc_user_id_fk');
        SafeForeignKey::addNullableForeignKey('user_email_cc_recipients', 'recipient_user_id', 'users', 'id', 'uecc_recipient_user_id_fk');

        SafeForeignKey::addNullableForeignKey('employee_invoices', 'employee_id', 'employees', 'id', 'employee_invoices_employee_id_fk');
        SafeForeignKey::addNullableForeignKey('employee_invoice_items', 'inv_id', 'employee_invoices', 'id', 'employee_invoice_items_inv_id_fk');

        SafeForeignKey::addNullableForeignKey('customer_invoices', 'customer_id', 'customers', 'id', 'customer_invoices_customer_id_fk');
        SafeForeignKey::addNullableForeignKey('customer_invoice_items', 'inv_id', 'customer_invoices', 'id', 'customer_invoice_items_inv_id_fk');
        SafeForeignKey::addNullableForeignKey('customer_invoice_items', 'item_id', 'items', 'id', 'customer_invoice_items_item_id_fk');
    }

    public function down(): void
    {
        SafeForeignKey::dropForeignKeyIfExists('customer_invoice_items', 'customer_invoice_items_item_id_fk');
        SafeForeignKey::dropForeignKeyIfExists('customer_invoice_items', 'customer_invoice_items_inv_id_fk');
        SafeForeignKey::dropForeignKeyIfExists('customer_invoices', 'customer_invoices_customer_id_fk');

        SafeForeignKey::dropForeignKeyIfExists('employee_invoice_items', 'employee_invoice_items_inv_id_fk');
        SafeForeignKey::dropForeignKeyIfExists('employee_invoices', 'employee_invoices_employee_id_fk');

        SafeForeignKey::dropForeignKeyIfExists('user_email_cc_recipients', 'uecc_recipient_user_id_fk');
        SafeForeignKey::dropForeignKeyIfExists('user_email_cc_recipients', 'uecc_user_id_fk');
        SafeForeignKey::dropForeignKeyIfExists('user_email_settings', 'ues_user_id_fk');
    }
};
