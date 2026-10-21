<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherSignatureSlot extends BaseModel
{
    use LogsActivity;

    public const ASSIGNEE_DEPARTMENT = 'department';

    public const ASSIGNEE_USER = 'user';

    public const ASSIGNEE_EMPLOYEE = 'employee';

    public const ASSIGNEE_LABEL_ONLY = 'label_only';

    protected $table = 'voucher_signature_slots';

    protected $fillable = [
        'company_id',
        'voucher_signature_config_id',
        'role_label',
        'assignee_type',
        'department_id',
        'user_id',
        'employee_id',
        'designation_override',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function config(): BelongsTo
    {
        return $this->belongsTo(VoucherSignatureConfig::class, 'voucher_signature_config_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Departments::class, 'department_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * @return list<string>
     */
    public static function assigneeTypes(): array
    {
        return [
            self::ASSIGNEE_LABEL_ONLY,
            self::ASSIGNEE_DEPARTMENT,
            self::ASSIGNEE_USER,
            self::ASSIGNEE_EMPLOYEE,
        ];
    }

    protected static function getModuleName(): string
    {
        return 'Voucher Signature Assignment';
    }
}
