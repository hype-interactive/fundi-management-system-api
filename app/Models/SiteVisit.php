<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SiteVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator_id',
        'ward_id',
        'site_name',
        'site_description',
        'visit_date',
        'gps_latitude',
        'gps_longitude',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'approved_at' => 'datetime',
        'gps_latitude' => 'decimal:8',
        'gps_longitude' => 'decimal:8',
    ];

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function photos()
    {
        return $this->hasMany(SiteVisitPhoto::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'site_visit_products')
            ->withPivot('quantity', 'notes');
    }

    public function fundis()
    {
        return $this->belongsToMany(Fundi::class, 'site_visit_fundis')
            ->withPivot('notes');
    }

    public function incentive()
    {
        return $this->hasOne(OperatorIncentive::class);
    }

    public function district()
    {
        return $this->ward->district();
    }

    public function region()
    {
        return $this->ward->district->region();
    }

    public function getLocationAttribute()
    {
        return $this->gps_latitude && $this->gps_longitude
            ? "Lat: {$this->gps_latitude}, Long: {$this->gps_longitude}"
            : 'Not specified';
    }
}
