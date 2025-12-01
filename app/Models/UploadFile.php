<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

class UploadFile extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['name', 'detail', 'path', 'uploaded_by'];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUploadedAtAttribute()
    {
        return $this->created_at->format('d M Y, h:i A');
    }
}
