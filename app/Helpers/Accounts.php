<?php

namespace App\Helpers;

use App\Models\Banks;
use App\Models\Customers;
use App\Models\LeasingCompanies;
use App\Models\LedgerEntry;
use App\Models\Riders;
use App\Models\Services;
use App\Models\Settings;
use App\Models\Transactions;

class Accounts
{

  public static function AccountTypes($id = null)
  {
    $types = [
      'Asset' => 'Asset',
      'Liability' => 'Liability',
      'Equity' => 'Equity',
      'Revenue' => 'Revenue',
      'Expense' => 'Expense'

    ];
    if ($id) {
      return $types[$id];
    } else {
      return $types;
    }
  }

  /**
   * Build dropdown options for accounts (optionally bold root-level/main parent accounts).
   *
   * @param array|\Illuminate\Support\Collection $items Items grouped by parent_id
   * @param mixed $selected Selected id
   * @param mixed $parentId Current parent id (null = root level)
   * @param string $prefix Prefix for nested display
   * @param bool $boldRootLevel If true, root-level options are rendered in bold (e.g. for Expense module)
   */
  public static function dropdown($items, $selected = null, $parentId = null, $prefix = '', $boldRootLevel = false)
  {
    $html = '';
    $select = '';

    if (isset($items[$parentId])) {
      foreach ($items[$parentId] as $item) {
        if ($selected) {
          if ($item->id == $selected) {
            $select = 'selected';
          } else {
            $select = '';
          }
        }

        $isRoot = $parentId === null;
        $style = ($boldRootLevel && $isRoot) ? ' style="font-weight: bold;"' : '';
        $html .= '<option value="' . $item->id . '" ' . $select . $style . '>' . $prefix . e($item->name) . '</option>';
        $html .= self::dropdown($items, $selected, $item->id, $prefix . '⮞ ', $boldRootLevel);
      }
    }

    return $html;
  }

  /**
   * Build dropdown for expense accounts with parent accounts bold and disabled (not selectable).
   * Only child (leaf) accounts are selectable.
   *
   * @param array $items Items grouped by parent_id ('_root_' key = root level)
   * @param mixed $selected Selected id
   * @param mixed $parentKey Current parent key ('_root_' = root level, or account id)
   * @param string $prefix Prefix for nested display
   */
  public static function expenseAccountsDropdown($items, $selected = null, $parentKey = '_root_', $prefix = '')
  {
    $html = '';

    if (!isset($items[$parentKey])) {
      return $html;
    }

    foreach ($items[$parentKey] as $item) {
      $select = ($selected && $item->id == $selected) ? 'selected' : '';
      $hasChildren = isset($items[$item->id]) && count($items[$item->id]) > 0;

      if ($hasChildren) {
        $html .= '<option value="" disabled style="font-weight: bold;">' . $prefix . e($item->name) . '</option>';
      } else {
        $html .= '<option value="' . $item->id . '" ' . $select . '>' . $prefix . e($item->name) . '</option>';
      }

      $html .= self::expenseAccountsDropdown($items, $selected, $item->id, $prefix . '⮞ ');
    }

    return $html;
  }


  public static function getRef($data)
  {
    if ($data['ref_name']) {
      if ($data['ref_name'] == 'Customer') {
        $row = Customers::find($data['ref_id']);
      }
      if ($data['ref_name'] == 'Rider') {
        $row = Riders::find($data['ref_id']);
      }
      if ($data['ref_name'] == 'Bank') {
        $row = Banks::find($data['ref_id']);
      }
      if ($data['ref_name'] == 'LeasingCompany') {
        $row = LeasingCompanies::find($data['ref_id']);
      }
      if ($data['ref_name'] == 'Account') {
        $row = \App\Models\Accounts::find($data['ref_id']);
      }
      return $row;
    }
  }

  public static function getBalance($account_id)
  {
    $balance = Transactions::where('account_id', $account_id)
      ->select(
        \DB::raw('SUM(debit) as total_debit'),
        \DB::raw('SUM(credit) as total_credit')
      )
      ->first();

    $finalBalance = ($balance->total_debit ?? 0) - ($balance->total_credit ?? 0);

    /* $balance = LedgerEntry::where('account_id', $account_id)->where('billing_month', date('Y-m-01'))->first();
    $finalBalance = $balance->closing_balance; */
    return number_format($finalBalance, 2);
  }
}
