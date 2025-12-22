<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class SimHistory extends Model
{
    use LogsActivity;
    public $table = 'sim_histories';

    public $fillable = [
        'sim_id',
        'rider_id',
        'notes',
        'note_date',
        'return_date',
        'assigned_by',
        'returned_by'
    ];
    protected $casts = [
        'notes' => 'string',
        'note_date' => 'date',
        'return_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'assigned_by' => 'string',
        'returned_by' => 'string'
    ];
    public static array $rules = [
        'sim_id' => 'required',
        'rider_id' => 'nullable',
        'notes' => 'nullable|string|max:65535',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
        'note_date' => 'nullable',
        'return_date' => 'nullable',
        'assigned_by' => 'nullable',
        'returned_by' => 'nullable'
    ];
    public function rider()
    {
        return $this->belongsTo(Riders::class, 'rider_id', 'id');
    }
    
    public function sim()
    {
        return $this->belongsTo(Sims::class, 'sim_id', 'id');
    }
}
