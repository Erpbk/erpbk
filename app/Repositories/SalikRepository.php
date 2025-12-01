<?php

namespace App\Repositories;

use App\Models\salik;

class SalikRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model(): string
    {
        return salik::class;
    }

    /**
     * @return array
     */
    public function getFieldsSearchable(): array
    {
        // Add searchable fields as needed
        return [];
    }
}
