<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Region extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function districts()
    {
        return $this->hasMany(District::class);
    }

    public function wards()
    {
        return $this->hasManyThrough(Ward::class, District::class);
    }
}
