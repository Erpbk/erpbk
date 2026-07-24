<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Traits\LogsActivity;
use App\Traits\HasActiveStatus;
use App\Traits\BranchScope;

class Riders extends BaseModel
{
  use SoftDeletes, LogsActivity, HasActiveStatus, BranchScope;

  public $table = 'riders';

  public $fillable = [
    'branch_id',
    'company_id',
    'name',
    'rider_id',
    'account_id',
    'email',
    'nationality',
    'doj',
    'designation',
    'emirate_hub',
    'rider_status_option',
    'emirate_id',
    'emirate_exp',
    'passport',
    'passport_expiry',
    'ethnicity',
    'dob',
    'license_no',
    'license_expiry',
    'road_permit',
    'road_permit_expiry',
    'created_by',
    'updated_by',
    'status',
    'rider_status',
    'display_status',
    'fleet_supervisor',
    'wps',
    'image_name',
    'person_code',
    'labor_card_number',
    'labor_card_expiry',
    'attendance',
    'customer_id',
    'rider_top_option_id',
    'recuriter',
    'recruiter_id',
    'deleted_by',
    'custom_field_values'
  ];

  protected $casts = [
    'custom_field_values' => 'array',
    'company_id' => 'integer',
    'name' => 'string',
    'email' => 'string',
    'doj' => 'string',
    'emirate_id' => 'string',
    'emirate_exp' => 'string',
    'passport' => 'string',
    'passport_expiry' => 'string',
    'ethnicity' => 'string',
    'dob' => 'string',
    'license_no' => 'string',
    'license_expiry' => 'string',
    'road_permit' => 'string',
    'road_permit_expiry' => 'string',
    'fleet_supervisor' => 'string',
    'wps' => 'string',
    'image_name' => 'string',
    'rider_status' => 'string',
    'display_status' => 'string',

    'person_code' => 'string',
    'labor_card_number' => 'string',
    'labor_card_expiry' => 'string',
    'attendance' => 'string',
    'rider_top_option_id' => 'integer',
    'recuriter' => 'string',
    'recruiter_id' => 'integer',
    'deleted_at' => 'datetime'
  ];

  /**
   * The attributes that should be included in the model's array form.
   *
   * @var array
   */
  protected $dates = ['deleted_at'];

  public static array $rules = [
    'company_id' => 'nullable|integer|exists:companies,id',
    'name' => 'nullable|string|max:191|unique:riders,name',
    'rider_id' => 'nullable|unique:riders,rider_id',
    'email' => 'nullable|string|max:191',
    'nationality' => 'nullable',
    'doj' => 'nullable',
    'emirate_id' => 'nullable|string|max:191',
    'emirate_exp' => 'nullable',
    'passport' => 'nullable|string|max:191|unique:riders,passport',
    'passport_expiry' => 'nullable',
    'ethnicity' => 'nullable|string|max:191',
    'dob' => 'nullable',
    'license_no' => 'nullable|string|max:191',
    'license_expiry' => 'nullable',
    'road_permit' => 'nullable|string|max:191',
    'road_permit_expiry' => 'nullable',
    'created_by' => 'nullable',
    'updated_by' => 'nullable',
    'created_at' => 'nullable',
    'updated_at' => 'nullable',
    'customer_id' => '',
    'rider_top_option_id' => 'nullable|integer|exists:rider_top_options,id',
    'status' => 'nullable',
    'fleet_supervisor' => 'nullable|string|max:50',
    'wps' => 'nullable|string|max:100',
    'image_name' => 'nullable|string|max:255',
    'person_code' => 'nullable|string|max:50',
    'labor_card_number' => 'nullable|string|max:100',
    'labor_card_expiry' => 'nullable',
    'recuriter' => 'nullable|string|max:255',
    'recruiter_id' => 'nullable|integer|exists:recruiters,id'
  ];

  /**
   * Human label + Bootstrap badge class for riders.status (bike assign/return lifecycle).
   * 1 = active on fleet, 0/2/3 = inactive/off bike, 4 = vacation hold, 5 = absconded (see BikesController::assignrider).
   *
   * @param  int|string|null  $status
   * @return array{label: string, badge: string}
   */
  public static function employmentStatusDisplay($status): array
  {
    $code = $status === null || $status === '' ? null : (int) $status;

    return match ($code) {
      1 => ['label' => 'Active', 'badge' => 'bg-label-success'],
      0, 2, 3 => ['label' => 'Inactive', 'badge' => 'bg-label-secondary'],
      4 => ['label' => 'Vacation', 'badge' => 'bg-label-warning'],
      5 => ['label' => 'Absconded', 'badge' => 'bg-label-danger'],
      default => [
        'label' => $code === null ? 'Not set' : 'Status ' . $code,
        'badge' => 'bg-label-secondary',
      ],
    };
  }

  /**
   * Whether a view-card option should update riders.status (bike assignment column).
   */
  public static function topOptionAffectsEmploymentStatus(?string $optionName): bool
  {
    $name = strtolower(trim((string) $optionName));

    return in_array($name, ['active', 'vacation', 'absconder', 'absconded', 'cancel', 'inactive'], true);
  }

  /**
   * Map rider view-card option to riders.status (bike assign / return lifecycle).
   */
  public static function employmentStatusCodeForTopOption(?string $optionName, ?Bikes $bike = null, bool $cleared = false): int
  {
    if ($cleared || $optionName === null || trim($optionName) === '') {
      $hasActiveBike = $bike && strcasecmp((string) ($bike->warehouse ?? ''), 'Active') === 0;

      return $hasActiveBike ? 1 : 3;
    }

    $name = strtolower(trim($optionName));

    return match ($name) {
      'active' => 1,
      'vacation' => 4,
      'absconder', 'absconded' => 5,
      'cancel', 'inactive' => 3,
      default => ($bike && strcasecmp((string) ($bike->warehouse ?? ''), 'Active') === 0) ? 1 : 3,
    };
  }

  /**
   * Rider option / flag badge (matches riders/table.blade.php).
   */
  public static function riderOptionStatusBadge(?string $optionText): ?array
  {
    $optionText = trim((string) $optionText);
    if ($optionText === '') {
      return null;
    }
    $normalized = strtolower($optionText);

    $badge = in_array($normalized, ['active', 'follow up', 'pro', 'walker', 'learning license'], true)
      ? 'bg-label-success'
      : ($normalized === 'absconder'
        ? 'bg-label-danger'
        : ($normalized === 'vacation'
          ? 'bg-label-warning'
          : 'bg-label-info'));

    return ['label' => $optionText, 'badge' => $badge];
  }

  /**
   * Combined status label for history (employment + option when present).
   */
  public static function historyStatusLabel($riderOrEmploymentStatus, ?string $riderStatus = null): string
  {
    $employmentCode = $riderOrEmploymentStatus instanceof self
      ? $riderOrEmploymentStatus->status
      : $riderOrEmploymentStatus;
    $optionText = $riderOrEmploymentStatus instanceof self
      ? trim((string) ($riderOrEmploymentStatus->rider_status ?? ''))
      : trim((string) ($riderStatus ?? ''));

    $employment = self::employmentStatusDisplay($employmentCode);
    if ($optionText !== '') {
      return $employment['label'] . ' · ' . $optionText;
    }

    return $employment['label'];
  }

  /**
   * @return array{employment: array{label: string, badge: string}, option: ?array{label: string, badge: string}}
   */
  public static function tableStatusBadges($riderOrEmploymentStatus, ?string $riderStatus = null): array
  {
    $employmentCode = $riderOrEmploymentStatus instanceof self
      ? $riderOrEmploymentStatus->status
      : $riderOrEmploymentStatus;
    $optionText = $riderOrEmploymentStatus instanceof self
      ? ($riderOrEmploymentStatus->rider_status ?? '')
      : $riderStatus;

    return [
      'employment' => self::employmentStatusDisplay($employmentCode),
      'option' => self::riderOptionStatusBadge($optionText),
    ];
  }

  /**
   * Persist combined table status on riders.display_status (when column exists).
   */
  public static function syncDisplayStatus(Riders $rider): void
  {
    if (!Schema::hasColumn('riders', 'display_status')) {
      return;
    }
    $label = self::historyStatusLabel($rider);
    if ((string) ($rider->display_status ?? '') !== $label) {
      $rider->display_status = $label;
      $rider->saveQuietly();
    }
  }

  /**
   * Extra SELECT columns for riders index: last employment status change + day count.
   *
   * @return list<\Illuminate\Contracts\Database\Query\Expression|string>
   */
  public static function employmentStatusDaysSelectColumns(): array
  {
    if (!Schema::hasTable('rider_histories')) {
      return [
        DB::raw('NULL as last_employment_status_change_date'),
        DB::raw('NULL as employment_status_days'),
      ];
    }

    $matchedDate = "(SELECT MAX(rh.effective_date) FROM rider_histories rh
      WHERE rh.rider_id = riders.id
      AND rh.event_type = 'status_change'
      AND CAST(JSON_UNQUOTE(JSON_EXTRACT(rh.meta, '$.new_employment_status')) AS UNSIGNED) = riders.status)";

    $fallbackDate = "(SELECT MAX(rh.effective_date) FROM rider_histories rh
      WHERE rh.rider_id = riders.id
      AND rh.event_type = 'status_change')";

    $lastDate = "COALESCE({$matchedDate}, {$fallbackDate}, DATE(riders.updated_at))";

    return [
      DB::raw("{$lastDate} as last_employment_status_change_date"),
      DB::raw("DATEDIFF(CURDATE(), {$lastDate}) as employment_status_days"),
    ];
  }

  /**
   * Resolve days since the rider's current employment status last changed.
   *
   * @param  self|object|array|null  $rider
   * @return array{days: int|null, changed_at: string|null}
   */
  public static function resolveEmploymentStatusDays($rider): array
  {
    if ($rider === null) {
      return ['days' => null, 'changed_at' => null];
    }

    $attrs = $rider instanceof self
      ? $rider->getAttributes()
      : (array) $rider;

    if (array_key_exists('employment_status_days', $attrs)) {
      return [
        'days' => $attrs['employment_status_days'] !== null && $attrs['employment_status_days'] !== ''
          ? (int) $attrs['employment_status_days']
          : null,
        'changed_at' => $attrs['last_employment_status_change_date'] ?? null,
      ];
    }

    $id = $attrs['id'] ?? null;
    if (! $id) {
      return ['days' => null, 'changed_at' => null];
    }

    $row = static::withTrashed()
      ->where('riders.id', $id)
      ->select('riders.id')
      ->addSelect(static::employmentStatusDaysSelectColumns())
      ->first();

    if (! $row) {
      return ['days' => null, 'changed_at' => null];
    }

    return [
      'days' => $row->employment_status_days !== null ? (int) $row->employment_status_days : null,
      'changed_at' => $row->last_employment_status_change_date ?? null,
    ];
  }

  /**
   * Attach employment_status_days / last_employment_status_change_date onto rider models (batch).
   *
   * @param  iterable<int, self|null>  $riders
   */
  public static function hydrateEmploymentStatusDays(iterable $riders): void
  {
    $collection = collect($riders)->filter(fn ($rider) => $rider instanceof self);
    if ($collection->isEmpty()) {
      return;
    }

    $needsHydration = $collection->filter(
      fn (self $rider) => ! array_key_exists('employment_status_days', $rider->getAttributes())
    );
    if ($needsHydration->isEmpty()) {
      return;
    }

    $ids = $needsHydration->pluck('id')->unique()->values();
    $rows = static::withTrashed()
      ->whereIn('riders.id', $ids)
      ->select('riders.id')
      ->addSelect(static::employmentStatusDaysSelectColumns())
      ->get()
      ->keyBy('id');

    foreach ($needsHydration as $rider) {
      $row = $rows->get($rider->id);
      $rider->setAttribute('employment_status_days', $row?->employment_status_days);
      $rider->setAttribute('last_employment_status_change_date', $row?->last_employment_status_change_date);
    }
  }

  public function scopeActive($query)
  {
    return $query->where('status', 1);
  }

  public function items()
  {
    return $this->hasMany(RiderItemPrice::class, 'RID', 'id');
  }

  public function branch()
  {
    return $this->belongsTo(Branch::class, 'branch_id', 'id');
  }
  public static function dropdown()
  {
    return self::dropdownForBranch(null);
  }

  /**
   * Riders eligible for bike assignment (active or inactive/off-bike; not vacation/absconded).
   *
   * @return array<int|string, string>
   */
  public static function dropdownForBikeAssign(?int $branchId = null): array
  {
    $query = self::query()->whereIn('status', [0, 1, 2, 3, 4]);

    if ($branchId !== null && $branchId > 0) {
      $query->where(function ($q) use ($branchId) {
        $q->where('branch_id', $branchId)->orWhereNull('branch_id');
      });
    }

    return $query
      ->select('id', DB::raw("CONCAT(COALESCE(rider_id, ''), '-', name) as full_name"))
      ->orderBy('name')
      ->pluck('full_name', 'id')
      ->prepend('Select', '')
      ->all();
  }

  /**
   * Active riders for SIM assignment (same branch as the SIM).
   */
  public static function dropdownForBranch(?int $branchId): array
  {
    $query = self::query();

    if ($branchId !== null && $branchId > 0) {
      $query->where(function ($q) use ($branchId) {
        $q->where('branch_id', $branchId)->orWhereNull('branch_id');
      });
    }

    return $query
      ->select('id', DB::raw("CONCAT(COALESCE(rider_id, ''), '-', name) as full_name"))
      ->orderBy('name')
      ->pluck('full_name', 'id')
      ->prepend('Select', '')
      ->all();
  }

  /**
   * All riders for SIM assignment (any status, all branches).
   *
   * @return array<int|string, string>
   */
  public static function dropdownForSimAssign(): array
  {
    $statusSuffix = Schema::hasColumn('riders', 'display_status')
      ? "CASE WHEN status = 1 THEN '' ELSE CONCAT(' (', COALESCE(NULLIF(display_status, ''), 'Inactive'), ')') END"
      : "CASE WHEN status = 1 THEN '' ELSE ' (Inactive)' END";

    return self::query()
      ->select('id', DB::raw("CONCAT(COALESCE(rider_id, ''), '-', name, {$statusSuffix}) as full_name"))
      ->orderBy('name')
      ->pluck('full_name', 'id')
      ->prepend('Select', '')
      ->all();
  }
  public function bikes()
  {
    return $this->hasOne(Bikes::class, 'rider_id', 'id');
  }

  public function histories()
  {
    return $this->hasMany(RiderHistory::class, 'rider_id', 'id')->orderByDesc('effective_date')->orderByDesc('id');
  }

  public function jobstatus()
  {
    return $this->hasOne(JobStatus::class, 'RID', 'id')->orderByDesc('id');
  }
  public function vendor()
  {
    return $this->hasOne(Vendors::class, 'id', 'VID');
  }
  public function customer()
  {
    return $this->hasOne(Customers::class, 'id', 'customer_id');
  }
  function account()
  {
    return $this->hasOne(Accounts::class, 'id', 'account_id');
  }
  function sim()
  {
    return $this->hasOne(Sims::class, 'assign_to', 'id');
  }
  function country()
  {
    return $this->hasOne(Countries::class, 'id', 'nationality');
  }

  function transactions()
  {
    return $this->hasMany(Transactions::class, 'account_id', 'account_id');
  }
  function activity()
  {
    return $this->hasMany(RiderActivities::class, 'rider_id', 'id')->where(DB::raw('DATE_FORMAT(date, "%Y-%m")'), '=', date('Y-m'));
  }

  function recruiter()
  {
    return $this->belongsTo(Recruiters::class, 'recruiter_id', 'id');
  }

  public function inventoryAssignments()
  {
    return $this->hasMany(RiderInventoryAssignment::class, 'rider_id', 'id');
  }
}
