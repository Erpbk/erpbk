<?php

namespace App\Models;

class CompanyDocumentType extends BaseModel
{
    protected $table = 'company_document_types';

    protected $fillable = [
        'company_id',
        'name',
        'key',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function defaultsForHeadOffice(): array
    {
        return [
            ['name' => 'Trade License', 'key' => 'tl', 'sort_order' => 1],
            ['name' => 'MOHRE Establishment', 'key' => 'mohre', 'sort_order' => 2],
            ['name' => 'Immigration Establishment Card', 'key' => 'imm', 'sort_order' => 3],
            ['name' => 'VAT Certificate', 'key' => null, 'sort_order' => 4],
            ['name' => 'Corporate Tax Certificate', 'key' => null, 'sort_order' => 5],
            ['name' => 'Tenancy Contract (Ejari)', 'key' => null, 'sort_order' => 6],
            ['name' => 'Chamber of Commerce', 'key' => null, 'sort_order' => 7],
            ['name' => 'Company Insurance', 'key' => null, 'sort_order' => 8],
        ];
    }

    public static function defaultsForBranch(): array
    {
        return [
            ['name' => 'Trade License', 'key' => 'tl', 'sort_order' => 1],
            ['name' => 'MOHRE Establishment', 'key' => 'mohre', 'sort_order' => 2],
            ['name' => 'Immigration Establishment Card', 'key' => 'imm', 'sort_order' => 3],
            ['name' => 'Tenancy Contract (Ejari)', 'key' => null, 'sort_order' => 4],
        ];
    }
}
