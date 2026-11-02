<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\ExcludesPendingDeletion;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    use BelongsToCompany;
    use ExcludesPendingDeletion;
}
