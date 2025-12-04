<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

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
        'qr_code_path'
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

    public function getFundiRankingAttribute()
    {
        return trim($this->ranking == 'fundi_kuu' ? 'Fundi Mkuu' : 'Fundi Msaidizi');
    }

      protected static function booted()
    {
        static::created(function ($fundi) {
            $fundi->generateQrCodeQuitely();
        });
    }

    function getAddressAttribute() {
        $regionName = $this->ward->district->region->name;
        $districtName = $this->ward->district->name;
        $wardName = $this->ward->name;

        return $wardName . "/". $districtName ."/". $regionName;
    }

    // In your Fundi model
    public function generateQrCode()
    {
        if ($this->qr_code_path && Storage::exists($this->qr_code_path)) {
            return $this->qr_code_path;
        }

        $qrCode = QrCode::format('svg')
        ->size(300)
        ->generate($this->unique_id); // or route to profile

        $filename = 'qrcodes/fundi_' . $this->id . '_' . time() . '.svg';
        Storage::disk('public')->put($filename, $qrCode);

        $this->update(['qr_code_path' => $filename]);

        return $filename;
    }

        public function generateQrCodeQuitely()
    {
        // Skip if already exists
        if ($this->qr_code_path && Storage::disk('public')->exists($this->qr_code_path)) {
            return $this->qr_code_path;
        }

        // Generate QR code
        $qrCode = QrCode::format('svg')
        ->size(300)
        ->generate($this->unique_id);// or url to profile

        // Save to storage
        $filename = 'qrcodes/fundi_' . $this->id . '_' . time() . '.svg';
        Storage::disk('public')->put($filename, $qrCode);

        // Update model without triggering events again
        $this->updateQuietly(['qr_code_path' => $filename]);

        return $filename;
    }



    /**
     * Get the approval status based on most recent action
     * Compares timestamps to determine if approved or rejected status takes precedence
     *
     * IMPORTANT: This method now properly handles cases where a fundi was:
     * - First approved, then rejected (shows as Rejected)
     * - First rejected, then approved (shows as Approved)
     * - The most recent action always takes precedence
     */
    public function getApprovalStatusAttribute(): string
    {
        // Get timestamps (use 0 if null for comparison)
        $rejectedTime = $this->rejected_at?->timestamp ?? 0;
        $approvedTime = $this->approved_at?->timestamp ?? 0;

        // If both actions exist, the most recent one takes precedence
        if ($rejectedTime > 0 && $approvedTime > 0) {
            return $rejectedTime > $approvedTime ? 'Rejected' : 'Approved';
        }

        // If only rejection exists
        if ($rejectedTime > 0 || $this->rejected_by !== null) {
            return 'Rejected';
        }

        // If only approval exists (must have both timestamp and approver)
        if ($approvedTime > 0 && $this->approved_by !== null) {
            return 'Approved';
        }

        // Default to pending if neither action has been taken
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
     * These scopes now properly handle the timestamp comparison logic
     */
    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at')
            ->whereNotNull('approved_by')
            ->where(function ($q) {
                // Either no rejection exists, or approval is more recent than rejection
                $q->whereNull('rejected_at')
                    ->whereNull('rejected_by')
                    ->orWhereRaw('approved_at > rejected_at');
            });
    }

    public function scopeRejected($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('rejected_at')
                ->orWhereNotNull('rejected_by');
        })->where(function ($q) {
            // Either no approval exists, or rejection is more recent than approval
            $q->whereNull('approved_at')
                ->whereNull('approved_by')
                ->orWhereRaw('rejected_at > approved_at');
        });
    }

    public function scopePending($query)
    {
        return $query->where(function ($q) {
            // No approval
            $q->whereNull('approved_at')
                ->orWhereNull('approved_by');
        })->where(function ($q) {
            // No rejection
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
