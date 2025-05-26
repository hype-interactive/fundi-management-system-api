<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Fundi extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'unique_id',
        'fname',
        'mname',
        'lname',
        'gender',
        'nida_number',
        'primary_phone',
        'transaction_phone',
        'ward_id',
        'ranking',
        'profile_photo',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'rejected_at',
        'rejected_by',
        'legacy_match',
        'legacy_id',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'legacy_match' => 'boolean',
    ];

    public function getFullNameAttribute()
    {
        return trim("{$this->fname} {$this->mname} {$this->lname}");
    }

    public function getApprovalStatusAttribute(): string
    {
        return match (true) {
            $this->rejected_at => 'Rejected',
            $this->approved_at => 'Approved',
            default => 'Pending',
        };
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function siteVisits()
    {
        return $this->belongsToMany(SiteVisit::class, 'site_visit_fundis');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function smsMessages()
    {
        return $this->hasMany(SmsMessage::class, 'recipient_id')
            ->where('recipient_type', 'fundi');
    }

    public function district()
    {
        return $this->ward->district();
    }

    public function region()
    {
        return $this->ward->district->region();
    }
}
