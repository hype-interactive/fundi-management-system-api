<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'recipient_type',
        'scheduled_at',
        'status',
        'created_by',
        'sent_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function filters()
    {
        return $this->hasMany(SmsCampaignFilter::class);
    }

    public function messages()
    {
        return $this->hasMany(SmsMessage::class);
    }
}
