<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'sms_campaign_id',
        'recipient_type',
        'recipient_id',
        'phone_number',
        'message',
        'status',
        'provider_message_id',
        'sent_at',
        'delivered_at',
        'failure_reason',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(SmsCampaign::class, 'sms_campaign_id');
    }

    public function recipient()
    {
        if ($this->recipient_type === 'fundi') {
            return $this->belongsTo(Fundi::class, 'recipient_id');
        } else {
            return $this->belongsTo(User::class, 'recipient_id');
        }
    }
}
