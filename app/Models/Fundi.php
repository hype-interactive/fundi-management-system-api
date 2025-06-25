<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

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
        'profile_photo_url',
        'profile_photo_public_id',
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

    /**
     * Get the approval status with proper priority handling
     * Priority order: Rejected > Approved > Pending
     */
    public function getApprovalStatusAttribute(): string
    {
        // Check for rejection first (highest priority)
        // A fundi is rejected if they have either rejected_at timestamp OR rejected_by user
        if ($this->rejected_at !== null || $this->rejected_by !== null) {
            return 'Rejected';
        }

        // Check for approval (second priority)
        // A fundi is approved if they have both approved_at timestamp AND approved_by user
        if ($this->approved_at !== null && $this->approved_by !== null) {
            return 'Approved';
        }

        // Default to pending if neither rejected nor fully approved
        return 'Pending';
    }

    /**
     * Alternative method if you want more granular status checking
     */
    public function getDetailedApprovalStatusAttribute(): string
    {
        // Most recent action takes precedence based on timestamps
        $rejectedTime = $this->rejected_at?->timestamp ?? 0;
        $approvedTime = $this->approved_at?->timestamp ?? 0;

        // If both timestamps exist, compare them
        if ($rejectedTime > 0 && $approvedTime > 0) {
            return $rejectedTime > $approvedTime ? 'Rejected' : 'Approved';
        }

        // Single action checks
        if ($rejectedTime > 0 || $this->rejected_by !== null) {
            return 'Rejected';
        }

        if ($approvedTime > 0 && $this->approved_by !== null) {
            return 'Approved';
        }

        return 'Pending';
    }

    /**
     * Helper methods for status checking
     */
    public function isApproved(): bool
    {
        return $this->approval_status === 'Approved';
    }

    public function isRejected(): bool
    {
        return $this->approval_status === 'Rejected';
    }

    public function isPending(): bool
    {
        return $this->approval_status === 'Pending';
    }

    /**
     * Scope methods for querying by approval status
     */
    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at')
            ->whereNotNull('approved_by')
            ->where(function ($q) {
                $q->whereNull('rejected_at')
                    ->whereNull('rejected_by');
            });
    }

    public function scopeRejected($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('rejected_at')
                ->orWhereNotNull('rejected_by');
        });
    }

    public function scopePending($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('approved_at')
                ->orWhereNull('approved_by');
        })->where(function ($q) {
            $q->whereNull('rejected_at')
                ->whereNull('rejected_by');
        });
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

    public function getProfilePhotoThumbnailAttribute()
    {
        if ($this->profile_photo_public_id) {
            return Cloudinary::url($this->profile_photo_public_id, [
                'width' => 150,
                'height' => 150,
                'crop' => 'fill',
                'quality' => 'auto',
                'fetch_format' => 'auto'
            ]);
        }
        return null;
    }

    public function getProfilePhotoAvatarAttribute()
    {
        if ($this->profile_photo_public_id) {
            return Cloudinary::url($this->profile_photo_public_id, [
                'width' => 50,
                'height' => 50,
                'crop' => 'fill',
                'quality' => 'auto',
                'fetch_format' => 'auto'
            ]);
        }
        return null;
    }
}
