<?php

/**
 * Cheque top-bar date filtering.
 *
 * Each cheque_top_categories.cheque_column maps to a filter mode when that column is a date/datetime field.
 * Add new columns here without changing query code.
 */
return [

  'default_mode' => 'exact',

  /*
  |--------------------------------------------------------------------------
  | Date / datetime columns on cheques (time is ignored when filtering)
  |--------------------------------------------------------------------------
  */
  'date_columns' => [
    'issue_date',
    'cheque_date',
    'cleared_date',
    'returned_date',
    'stop_payment_date',
    'due_date',
    'billing_month',
  ],

  /*
  |--------------------------------------------------------------------------
  | Per-column filter behaviour
  |--------------------------------------------------------------------------
  | exact    – match calendar day of option value (whereDate = selected date)
  | upcoming – column date >= selected date (start of day)
  | range    – use cheque_top_date_from / cheque_top_date_to request params;
  |            if only option date is set, treats as exact day
  */
  'columns' => [
    'cheque_date' => [
      'mode' => 'upcoming',
      'label' => 'Upcoming Cheques',
    ],
    'issue_date' => [
      'mode' => 'exact',
      'label' => 'Issued Cheques',
    ],
    'cleared_date' => [
      'mode' => 'exact',
      'label' => 'Cleared Cheques',
    ],
    'returned_date' => [
      'mode' => 'exact',
    ],
    'stop_payment_date' => [
      'mode' => 'exact',
    ],
    'due_date' => [
      'mode' => 'upcoming',
    ],
  ],

];
