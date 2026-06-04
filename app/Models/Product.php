<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'visibility',
        'availability',
        'photo_url',
        'photo_public_id',
    ];

    public function siteVisits()
    {
        return $this->belongsToMany(SiteVisit::class, 'site_visit_products')
            ->withPivot('quantity', 'notes');
    }
}
