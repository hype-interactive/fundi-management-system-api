<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    use HasFactory;

    protected $fillable = [
        'district_id',
        'name',
    ];

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function region()
    {
        return $this->district->region();
    }

    public function fundis()
    {
        return $this->hasMany(Fundi::class);
    }

    public function siteVisits()
    {
        return $this->hasMany(SiteVisit::class);
    }
}
