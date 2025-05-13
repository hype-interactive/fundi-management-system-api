<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteVisitProduct extends Model
{
    protected $table = 'site_visit_products';

    protected $fillable = [
        'site_visit_id',
        'product_id',
        'quantity',
        'notes',
    ];

    public function siteVisit()
    {
        return $this->belongsTo(SiteVisit::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
