# Voucher Approval — Phase 6 coverage checklist

All live accounting vouchers use `App\Models\Vouchers` + `HasApprovalWorkflow`.
Ledger hold is centralized in `TransactionService::recordTransaction` and `Transactions::creating`.

| Code | Module | Create path covered by central trait + ledger hold | Detail UI panel | Notes |
|------|--------|-----------------------------------------------------|-----------------|-------|
| JV | vouchers | Yes (`VoucherService::JournalVoucher`) | show_modal | |
| TRF | vouchers | Yes (`FundsTransfer`) | show_modal | |
| PV | cash_banks | Yes (`PaymentController` → `Transactions::create` boot) | show_modal / payment doc | |
| RV | cash_banks | Yes (`ReceiptController`) | show_modal | |
| AL | riders/employees | Yes | rider document + show_modal | |
| COD | riders | Yes | rider document | |
| PN | riders/employees | Yes | rider document | |
| INC | riders/employees | Yes | rider document | |
| PAY | riders | Yes | rider document | |
| VC | riders | Yes | rider document | |
| EXP | expenses | Yes | show_modal | |
| LV | visa_expense | Yes | show_modal | |
| VL | visa_expense | Yes | show_modal | |
| LE | license_expense | Yes | show_modal | |
| VR | bikes | Yes | show_modal | |
| RFV | rta_fines | Yes | show_modal | |
| SV | rta_saliks | Yes | show_modal | |
| GV | garage_items | Yes | show_modal | |
| RI | riders invoices | Yes | show_modal | |
| VP | vat | Yes | show_modal | |
| VV | vat | Yes (type seeded in migration) | show_modal | |
| BL | loans | Yes | show_modal | |
| FAV | assets | Yes | show_modal | |
| FDV | assets | Yes | show_modal | |
| IL | inventory/sim/fuel loss | Yes | show_modal | |

**Not voucher rows (excluded):** legal_cases / bike_maintenance voucher-named views without `Vouchers::create`.

**Known orphans (tracked, not blockers):** `riders/storevisaloan` route without method; `SimInvoicesController::storePaymentVoucher` without clear named route.

**New types:** register in `config/voucher_approval.php` + permission leaf in `tenant_module_permissions.php` (or rider submodule `extra_actions`), then sync permissions.
