<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class RiderInvoiceTemplate extends BaseModel
{
    public const FALLBACK_VIEW = 'rider_invoices.templates.modern';

    public static function isSchemaReady(): bool
    {
        return Schema::hasTable('rider_invoice_templates')
            && Schema::hasColumn('rider_invoices', 'template_id');
    }
    public const LAYOUT_MODERN = 'modern';

    public const LAYOUT_CLASSIC = 'classic';

    public const LAYOUTS = [
        self::LAYOUT_MODERN => 'Modern Card',
        self::LAYOUT_CLASSIC => 'Classic Sales',
    ];

    protected $table = 'rider_invoice_templates';

    protected $fillable = [
        'company_id',
        'template_name',
        'layout_key',
        'description',
        'is_default',
        'status',
        'display_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'status' => 'boolean',
        'display_order' => 'integer',
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(RiderInvoices::class, 'template_id');
    }

    public function setAsDefault(): void
    {
        static::query()
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->is_default = true;
        $this->save();
    }

    public function viewName(): string
    {
        $key = array_key_exists($this->layout_key, self::LAYOUTS)
            ? $this->layout_key
            : self::LAYOUT_MODERN;

        return 'rider_invoices.templates.'.$key;
    }
}
