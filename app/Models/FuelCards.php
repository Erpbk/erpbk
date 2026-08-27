<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;
use App\Traits\BranchScope;

class FuelCards extends BaseModel
{
    use LogsActivity, BranchScope;

    public $table = "fuel_cards";

    /** Assigned to a rider. */
    public const STATUS_ACTIVE = 'Active';

    /** Held by the office, available to assign. */
    public const STATUS_IN_OFFICE = 'In Office';

    /** Taken out of service; cannot be assigned until reactivated. */
    public const STATUS_DEACTIVATED = 'Deactivated';

    /** Lost or never returned; the holder was charged via an IL voucher. */
    public const STATUS_LOST = 'Lost';

    /**
     * 'assigned_to' is deliberately omitted: rider assignment is owned by
     * FuelCardHistoryController::assign()/return() so that every change is
     * mirrored into fuel_card_histories.
     */
    protected $fillable = [
        'branch_id',
        'card_number',
        'fuel_company_id',
        'service_charges',
        'card_issue_date',
        'remarks',
        'status',
        'created_by',
        'updated_by',
        'bike_no',
        'lost_date',
        'lost_rider_id',
        'lost_amount',
        'lost_voucher_id',
        'lost_trans_code',
        'lost_remarks',
        'lost_by',
    ];

    protected $casts = [
        'card_number' => 'string',
        'fuel_company_id' => 'integer',
        'service_charges' => 'decimal:2',
        'card_issue_date' => 'date',
        'remarks' => 'string',
        'status' => 'string',
        'assigned_to' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'bike_no' => 'string',
        'attachment' => 'string',
        'lost_date' => 'date',
        'lost_rider_id' => 'integer',
        'lost_amount' => 'decimal:2',
        'lost_voucher_id' => 'integer',
        'lost_trans_code' => 'string',
        'lost_remarks' => 'string',
        'lost_by' => 'integer',
    ];

    public static array $rules = [
        'card_number' => 'required|string|min:16',
        'fuel_company_id' => 'required|exists:fuel_companies,id',
        'service_charges' => 'nullable|numeric|min:0',
        'card_issue_date' => 'required|date',
        'remarks' => 'nullable|string|max:1000',
        'status' => 'required|string|max:255',
        'assigned_to' => 'nullable|numeric',
        'created_by' => 'nullable|numeric',
        'updated_by' => 'nullable|numeric',
        'branch_id' => 'nullable|exists:branches,id',
    ];
    public function rider()
    {

        return $this->belongsTo(Riders::class, 'assigned_to', 'id');
    }

    public function histories()
    {
        return $this->hasMany(FuelCardHistory::class, 'card_id', 'id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInOffice($query)
    {
        return $query->where('status', self::STATUS_IN_OFFICE);
    }

    public function scopeDeactivated($query)
    {
        return $query->where('status', self::STATUS_DEACTIVATED);
    }

    public function isDeactivated(): bool
    {
        return $this->status === self::STATUS_DEACTIVATED;
    }

    public function isLost(): bool
    {
        return $this->status === self::STATUS_LOST;
    }

    /**
     * A card can only go to a rider when it is sitting in the office. Deactivated
     * and lost cards are out of service, and an Active card is already held.
     */
    public function isAssignable(): bool
    {
        return !$this->assigned_to && !$this->isDeactivated() && !$this->isLost();
    }

    /**
     * Bike recorded on this card when it was last assigned or portal-updated.
     * Older rows may store a bike id; newer rows store the plate.
     */
    public function recordedBike(): ?Bikes
    {
        $stored = trim((string) $this->bike_no);
        if ($stored === '') {
            return null;
        }

        $byPlate = Bikes::where('plate', $stored)->first();
        if ($byPlate) {
            return $byPlate;
        }

        if (ctype_digit($stored)) {
            return Bikes::find((int) $stored);
        }

        return Bikes::whereRaw("CONCAT(COALESCE(emirates, ''), '-', plate) = ?", [$stored])->first();
    }

    public static function formatBikeLabel(?Bikes $bike, ?string $fallback = null): string
    {
        if ($bike) {
            $label = trim(($bike->emirates ? $bike->emirates . '-' : '') . ($bike->plate ?? ''), '-');
            if ($label !== '') {
                return $label;
            }
        }

        $fallback = trim((string) $fallback);

        return $fallback !== '' ? $fallback : 'Not recorded';
    }

    public function recordedBikeLabel(): string
    {
        return self::formatBikeLabel($this->recordedBike(), $this->bike_no);
    }

    public function currentRiderBikeLabel(): string
    {
        $bike = $this->rider?->bikes;
        if (!$bike) {
            return 'No bike assigned';
        }

        return self::formatBikeLabel($bike);
    }

    public function assigneeIsAbsconded(): bool
    {
        return (bool) $this->rider?->isAbsconded();
    }

    /**
     * Card is held by a rider who currently has no bike.
     */
    public function hasNoVehicleAssigned(): bool
    {
        return (bool) $this->assigned_to && !$this->rider?->bikes;
    }

    /**
     * Rider still has a bike, but it is not the one recorded on this card.
     */
    public function vehicleChanged(): bool
    {
        if (!$this->assigned_to) {
            return false;
        }

        $current = $this->rider?->bikes;
        if (!$current) {
            return false;
        }

        $stored = trim((string) $this->bike_no);
        if ($stored === '') {
            return true;
        }

        $currentLabel = self::formatBikeLabel($current);

        return $stored !== (string) $current->plate
            && $stored !== (string) $current->id
            && $stored !== $currentLabel;
    }

    /**
     * True when the fuel portal still needs a bike update for this card.
     */
    public function needsBikePortalUpdate(): bool
    {
        return $this->vehicleChanged();
    }

    public function scopeWhereAssigneeAbsconded($query)
    {
        return $query->whereNotNull('assigned_to')->whereHas('rider', function ($riders) {
            $riders->whereAbsconded();
        });
    }

    public function scopeWhereNoVehicleAssigned($query)
    {
        return $query->whereNotNull('assigned_to')->whereDoesntHave('rider.bikes');
    }

    public function scopeWhereVehicleChanged($query)
    {
        return $query->whereNotNull('assigned_to')
            ->whereHas('rider.bikes')
            ->where(function ($q) {
                $q->whereRaw("TRIM(COALESCE(fuel_cards.bike_no, '')) = ''")
                    ->orWhereHas('rider.bikes', function ($bikeQ) {
                        $bikeQ->whereColumn('bikes.plate', '<>', 'fuel_cards.bike_no')
                            ->whereRaw('CAST(bikes.id AS CHAR) <> CAST(fuel_cards.bike_no AS CHAR)')
                            ->whereRaw("TRIM(BOTH '-' FROM CONCAT(COALESCE(bikes.emirates, ''), '-', bikes.plate)) <> fuel_cards.bike_no");
                    });
            });
    }

    /**
     * Rider who should be charged if this card is lost: the current holder, or the
     * most recent one when the card was already returned.
     */
    public function chargeableRider(): ?Riders
    {
        if ($this->assigned_to) {
            return $this->rider;
        }

        $lastHistory = $this->histories()
            ->whereNotNull('assigned_to')
            ->orderByDesc('assign_date')
            ->orderByDesc('id')
            ->first();

        return $lastHistory ? Riders::find($lastHistory->assigned_to) : null;
    }

    public function lostRider()
    {
        return $this->belongsTo(Riders::class, 'lost_rider_id', 'id');
    }

    public function lostBy()
    {
        return $this->belongsTo(User::class, 'lost_by', 'id');
    }

    public function lostVoucher()
    {
        return $this->belongsTo(Vouchers::class, 'lost_voucher_id', 'id');
    }

    public function lostVoucherLabel(): ?string
    {
        if (!$this->lost_voucher_id) {
            return null;
        }

        return 'IL-' . str_pad((string) $this->lost_voucher_id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Fuel transactions recorded against this card number.
     */
    public function fuelData()
    {
        return $this->hasMany(FuelData::class, 'card_no', 'card_number');
    }

    /**
     * @return array{label: string, badge: string}
     */
    public static function statusDisplay(mixed $status): array
    {
        return match ((string) $status) {
            self::STATUS_ACTIVE => ['label' => 'Active', 'badge' => 'bg-success'],
            self::STATUS_IN_OFFICE => ['label' => 'In Office', 'badge' => 'bg-info'],
            self::STATUS_DEACTIVATED => ['label' => 'Deactivated', 'badge' => 'bg-danger'],
            self::STATUS_LOST => ['label' => 'Lost', 'badge' => 'bg-dark'],
            // Legacy rows written before the In Office / Deactivated split.
            'Inactive' => ['label' => 'In Office', 'badge' => 'bg-info'],
            default => ['label' => 'In Office', 'badge' => 'bg-info'],
        };
    }

    /**
     * Find rider for specific date in card history
     */

    public function findRiderForDate($transactionDate)
    {

        $tripDate = $transactionDate ?? null;

        // Check card history for rider assigned on or before transaction date
        $history = $this->histories()
            ->whereDate('assign_date', '<=', $tripDate)
            ->where(function ($q) use ($tripDate) {
                $q->whereNull('return_date')
                    ->orWhereDate('return_date', '>=', $tripDate);
            })
            ->orderBy('assign_date', 'desc')
            ->first();

        if ($history && $history->assigned_to) {
            return Riders::find($history->assigned_to);
        }

        return null;
    }

    public function fuelCompany()
    {
        return $this->belongsTo(FuelCompany::class, 'fuel_company_id', 'id');
    }
}
