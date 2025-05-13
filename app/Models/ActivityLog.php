<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'log_type',
        'description',
        'model_type',
        'model_id',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        if ($this->model_type && $this->model_id) {
            $modelClass = '\\App\\Models\\' . $this->model_type;
            return $modelClass::find($this->model_id);
        }
        return null;
    }
}
