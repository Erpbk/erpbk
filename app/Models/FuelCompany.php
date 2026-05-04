<?php

namespace App\Models;

use App\Helpers\IConstants;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasActiveStatus;
use App\Traits\BranchScope;

class FuelCompany extends BaseModel
{
    use LogsActivity, HasActiveStatus, SoftDeletes, BranchScope;

    public $table = 'fuel_companies';

    public $fillable = [
        'branch_id',
        'name',
        'company_contact',
        'email',
        'address',
        'status',
        'account_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'name' => 'string',
        'company_contact' => 'string',
        'email' => 'string',
        'address' => 'string',
        'status' => 'integer',
    ];

    protected $dates = ['deleted_at'];

    public static array $rules = [
        'name' => 'required|string|max:255',
        'company_contact' => 'nullable|string|max:255',
        'email' => 'nullable|email|max:255',
        'address' => 'nullable|string|max:500',
        'branch_id' => 'nullable',
        'status' => 'nullable',
    ];

    public function account()
    {
        return $this->hasOne(Accounts::class, 'id', 'account_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transactions::class, 'account_id', 'account_id');
    }

    public static function dropdown()
    {
        return self::select('id', 'name')->where('status', IConstants::ACTIVE)->orderBy('name')->pluck('name', 'id')->prepend('Select', '');
    }
}
