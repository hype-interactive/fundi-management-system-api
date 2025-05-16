<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteVisitFundi extends Model
{
    use HasFactory;
    
    protected $table = 'site_visit_fundis';

    protected $fillable = [
        'site_visit_id',
        'fundi_id',
        'notes',
    ];

    public function siteVisit()
    {
        return $this->belongsTo(SiteVisit::class);
    }

    public function fundi()
    {
        return $this->belongsTo(Fundi::class);
    }
}
