<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FundiAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'fundi_id',
        'event_key',
        'operator_id',
        'scanned_at',
    ];

    public function fundi()
    {
        return $this->belongsTo(Fundi::class);
    }
}
