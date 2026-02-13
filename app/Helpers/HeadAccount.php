<?php

namespace App\Helpers;

interface HeadAccount
{
  const RIDER = 1;
  const BANK = 994;
  const SALARY_ACCOUNT = 1103;
  const TAX_ACCOUNT = 1023;
  const ADVANCE_LOAN = 1135;
  const RTA_FINE = 1235;
  const SALARIES_PAYABLE = 1200; // Add the correct GL code for Salaries Payable here
  const COD_ACCOUNT = 1219; // COD Account
  const LEASING_EXPENSE_ACCOUNT = 1129; // Leasing company invoice debit (expense) account – must exist in Chart of Accounts
  const PENALTY_ACCOUNT = 1017; // Penalty Account
  const INCENTIVE_ACCOUNT = 1009; // Incentive Account
  const PAYMENT_ACCOUNT = 994; // Payment Account (Using Bank account for payments)
  const VENDOR_CHARGES_ACCOUNT = 1005; // Vendor Charges Account
}
