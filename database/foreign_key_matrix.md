# Foreign Key Matrix (Additive Refactor)

This matrix tracks relationship-style columns and how they are handled in the additive FK rollout.

| Table | Column | Parent | Classification | Wave | Notes |
|---|---|---|---|---|---|
| user_email_settings | user_id | users.id | fk_candidate | 1 | legacy table intentionally skipped FK previously |
| user_email_cc_recipients | user_id | users.id | fk_candidate | 1 | nullable cleanup before FK |
| user_email_cc_recipients | recipient_user_id | users.id | fk_candidate | 1 | nullable cleanup before FK |
| employee_invoices | employee_id | employees.id | fk_candidate | 1 | add nullable FK |
| employee_invoice_items | inv_id | employee_invoices.id | fk_candidate | 1 | child invoice pointer |
| customer_invoices | customer_id | customers.id | fk_candidate | 1 | direct belongsTo in model |
| customer_invoice_items | inv_id | customer_invoices.id | fk_candidate | 1 | aligns with model fix |
| customer_invoice_items | item_id | items.id | fk_candidate | 1 | product link |
| rider_invoice_items | inv_id | rider_invoices.id | fk_candidate | 2 | high-volume child table |
| rider_invoice_items | item_id | items.id | fk_candidate | 2 | high-volume child table |
| rider_invoices | rider_id | riders.id | fk_candidate | 2 | nullable cleanup before FK |
| rider_invoices | vendor_id | vendors.id | fk_candidate | 2 | nullable cleanup before FK |
| supplier_invoices | supplier_id | suppliers.id | fk_candidate | 2 | nullable cleanup before FK |
| supplier_invoices | created_by | users.id | fk_candidate | 4 | audit pass |
| supplier_invoices | updated_by | users.id | fk_candidate | 4 | audit pass |
| supplier_invoice_items | inv_id | supplier_invoices.id | fk_candidate | 2 | nullable cleanup before FK |
| supplier_invoice_items | item_id | items.id | fk_candidate | 2 | nullable cleanup before FK |
| payments | bank_id | banks.id | fk_candidate | 2 | core finance relation |
| payments | voucher_id | vouchers.id | fk_candidate | 2 | nullable cleanup before FK |
| receipts | account_id | accounts.id | fk_candidate | 2 | nullable cleanup before FK |
| receipts | bank_id | banks.id | fk_candidate | 2 | nullable cleanup before FK |
| receipts | leasing_company_id | leasing_companies.id | fk_candidate | 2 | nullable cleanup before FK |
| receipts | voucher_id | vouchers.id | fk_candidate | 2 | nullable cleanup before FK |
| cheques | bank_id | banks.id | fk_candidate | 2 | non-null in schema, cleanup still applied |
| cheques | voucher_id | vouchers.id | fk_candidate | 2 | nullable cleanup before FK |
| riders | branch_id | branches.id | fk_candidate | 3 | core table |
| riders | account_id | accounts.id | fk_candidate | 3 | column type mismatch handled by compatibility checks |
| riders | nationality | countries.id | fk_candidate | 3 | renamed semantic key, still relational |
| riders | recruiter_id | recruiters.id | fk_candidate | 3 | nullable cleanup before FK |
| riders | deleted_by | users.id | fk_candidate | 4 | audit pass |
| bikes | rider_id | riders.id | fk_candidate | 3 | core table |
| bikes | branch_id | branches.id | fk_candidate | 3 | nullable cleanup before FK |
| bikes | created_by | users.id | fk_candidate | 4 | audit pass |
| bikes | updated_by | users.id | fk_candidate | 4 | audit pass |
| bikes | deleted_by | users.id | fk_candidate | 4 | audit pass |
| vouchers | branch_id | branches.id | fk_candidate | 3 | nullable cleanup before FK |
| vouchers | rider_id | riders.id | fk_candidate | 3 | nullable cleanup before FK |
| vouchers | vendor_id | vendors.id | fk_candidate | 3 | nullable cleanup before FK |
| vouchers | lease_company | leasing_companies.id | fk_candidate | 3 | nonstandard name |
| vouchers | deleted_by | users.id | fk_candidate | 4 | audit pass |
| vouchers | Created_By | users.id | fk_candidate | 4 | legacy mixed-case column |
| vouchers | Updated_By | users.id | fk_candidate | 4 | legacy mixed-case column |
| transactions | account_id | accounts.id | fk_candidate | 3 | add FK with orphan->NULL policy |
| transactions | branch_id | branches.id | fk_candidate | 3 | add FK with orphan->NULL policy |
| transactions | deleted_by | users.id | fk_candidate | 4 | audit pass |
| rta_fines | rider_id | riders.id | fk_candidate | 3 | already partially indexed |
| rta_fines | bike_id | bikes.id | fk_candidate | 3 | nullable cleanup before FK |
| rta_fines | debit_account_id | accounts.id | fk_candidate | 3 | new FK in this refactor |
| rta_fines | branch_id | branches.id | fk_candidate | 3 | nullable cleanup before FK |
| rta_fines | deleted_by | users.id | fk_candidate | 4 | audit pass |
| ledger_entries | account_id | accounts.id | fk_candidate | 3 | ensure FK exists safely |
| attendances | ref_id + ref_type | morph target | polymorphic | excluded | intentionally not a standard FK |
| transactions | reference_id + reference_type | polymorphic target | polymorphic | excluded | intentionally not a standard FK |
| personal_access_tokens | tokenable_id + tokenable_type | polymorphic target | polymorphic | excluded | framework default |
| vouchers | trans_code | business key | business_key | excluded | links to transactions by domain code |
| visa_expenses | trans_code | business key | business_key | excluded | links to vouchers/transactions by code |
| visa_installment_plans | rider_id | ambiguous (riders/accounts) | deprecated | excluded | requires domain decision before strict FK |

