<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmsCampaignFilter extends Model
{
    use HasFactory;

    protected $fillable = [
        'sms_campaign_id',
        'filter_type',
        'filter_value',
    ];

    public $timestamps = false;

    public function campaign()
    {
        return $this->belongsTo(SmsCampaign::class, 'sms_campaign_id');
    }
}
