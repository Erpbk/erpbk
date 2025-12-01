<?php

namespace App\Exports;
use App\Models\RiderActivities;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

use Carbon\Carbon;

class MonthlyActivityExport implements FromCollection, WithHeadings, WithMapping
{
  protected $month;

  public function __construct($id, $month)
  {
    $this->id = $id;
    $this->month = $month;
  }

  public function collection()
  {
    return RiderActivities::whereMonth('date', Carbon::parse($this->month)->month)
      ->whereYear('created_at', Carbon::parse($this->month)->year)
      ->where('rider_id', $this->id)
      ->get(
        [
          'id',
          'date',
          'rider_id',
          'payout_type',
          'delivered_orders',
          'ontime_orders_percentage',
          'rejected_orders',
          'login_hr',
          'delivery_rating'
        ]
      ); // select relevant columns
  }
  public function map($activity): array
  {
    return [
      $activity->date,
      $activity->rider->rider_id ?? '', // prevent null error if rider missing
      $activity->rider->name ?? '',
      $activity->payout_type,
      $activity->delivered_orders,
      $activity->ontime_orders_percentage,
      $activity->rejected_orders,
      $activity->login_hr,
      $activity->delivery_rating ?? 0,
    ];
  }

  public function headings(): array
  {
    return [
      'Date',
      'Rider ID',
      'Rider Name',
      'Payout',
      'Delivered',
      'Ontime%',
      'Rejected',
      'HR',
      'Rating',
    ];
  }
}
