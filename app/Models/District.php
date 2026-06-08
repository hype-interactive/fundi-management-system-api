<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory;

    protected $fillable = [
        'region_id',
        'name',
    ];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function wards()
    {
        return $this->hasMany(Ward::class);
    }

    public function operators()
    {
        return $this->belongsToMany(User::class, 'operator_districts');
    }

    public function fundis()
    {
        return $this->hasManyThrough(Fundi::class, Ward::class, 'district_id', 'ward_id');
    }
}
