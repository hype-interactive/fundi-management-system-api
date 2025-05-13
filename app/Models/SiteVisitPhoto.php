<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SiteVisitPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_visit_id',
        'photo_path',
    ];

    public $timestamps = false;

    public function siteVisit()
    {
        return $this->belongsTo(SiteVisit::class);
    }
}
