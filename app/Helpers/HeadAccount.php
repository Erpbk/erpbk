<?php

namespace App\Helpers;

final class HeadAccount
{
  const VAT_ON_SALES = 1025; // VAT on Sales Account
  const RTA_FINE = 2497; // RTA Fine Account
  const SIM_EXPENSE_ACCOUNT = 2394; // SIM Expense Account
  const VAT_PURCHASE_ACCOUNT = 2395; // VAT Purchase Account
  const FUEL_ADMIN_CHARGES = 2501; // Fuel Admin Charges Account
  const SALIK_ASSET_ACCOUNT = 1715; // Salik Asset Account
  const SALIK_ADMIN_CHARGES = 1003; // Salik Admin Charges Account
  const SALIK_PAYABLE_ACCOUNT = 2310; // Salik Payable Account
  const RTA_ADMIN_CHARGES = 2473; // RTA Admin Charges Account
  const RTA_SERVICE_CHARGES = 2513; // RTA Service Charges Account
  const LEASING_EXPENSE_ACCOUNT = 2493; // Leasing Expense Account
  const VEHICAL_INCOME = 2502; // Vehical Income Account
  const BIKE_MAINTENANCE_ACCOUNT = 2468; // Bike Maintenance Account
  const GARAGE_ACCOUNT = 2491; // Garage Items Account
  const SALARY_ACCOUNT = 2492; // Salary Account
  const STAFF_ACCOUNT = 2464; // Staff Account
  const SALES_ACCOUNT = 2514; // Sales Account
  const OTHER_EXPENSES = 2388;
  const VISA_EXPENSE_ACCOUNT = 2515; // Visa Expense Account
  const BIKE_REGISTRATION_EXPENSE_ACCOUNT = 2516; // Bike Registration Expense Account
  const INVENTORY_LOSS = 2519; // Inventory Loss Account
  const LICENSE_EXPENSE_ACCOUNT = 2520; // Dubai Driving License Expense Account


  /**
   * Head accounts used when recording salik payment vouchers (PV).
   */
  public static function salikPaymentAccountLabels(): array
  {
    return [
      self::SALIK_PAYABLE_ACCOUNT => 'Salik Payable',
      self::VAT_PURCHASE_ACCOUNT => 'VAT on Purchase',
      self::SALIK_ASSET_ACCOUNT => 'Salik Asset',
      self::SALIK_ADMIN_CHARGES => 'Salik Admin Charges',
    ];
  }

  /**
   * Head accounts used when charging salik to riders/companies (SV).
   */
  public static function salikVoucherAccountLabels(): array
  {
    return [
      self::SALIK_PAYABLE_ACCOUNT => 'Salik Payable',
      self::VAT_ON_SALES => 'VAT on Sales',
      self::SALIK_ADMIN_CHARGES => 'Salik Admin Charges',
    ];
  }
}
