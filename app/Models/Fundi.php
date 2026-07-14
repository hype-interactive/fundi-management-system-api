<?php

namespace App\Models;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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
        'qr_code_path',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'legacy_match' => 'boolean',
    ];

    /**
     * Relationships that should always be loaded to prevent N+1
     */
    protected $with = [];

    /**
     * Get full name accessor
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->fname} {$this->mname} {$this->lname}");
    }

    /**
     * Get fundi ranking formatted
     */
    public function getFundiRankingAttribute(): string
    {
        return $this->ranking === 'fundi_kuu' ? 'Fundi Mkuu' : 'Fundi Msaidizi';
    }

    /**
     * Boot method for model events
     */
    protected static function booted(): void
    {
        static::created(function ($fundi) {
            $fundi->generateQrCodeQuietly();
        });
    }

    /**
     * Get formatted address attribute
     * WARNING: Always eager load 'ward.district.region' to prevent N+1 queries
     */
    public function getAddressAttribute(): string
    {
        // Check if relationship is loaded to prevent N+1
        if (
            ! $this->relationLoaded('ward') ||
            ! $this->ward->relationLoaded('district') ||
            ! $this->ward->district->relationLoaded('region')
        ) {
            Log::warning('Address accessor called without eager loading relationships. This may cause N+1 queries.', [
                'fundi_id' => $this->id,
            ]);
        }

        $regionName = $this->ward->district->region->name ?? '';
        $districtName = $this->ward->district->name ?? '';
        $wardName = $this->ward->name ?? '';

        return "{$wardName}/{$districtName}/{$regionName}";
    }

    /**
     * Generate QR code (public method with event firing)
     */
    public function generateQrCode(): string
    {
        if ($this->qr_code_path && Storage::disk('public')->exists($this->qr_code_path)) {
            return $this->qr_code_path;
        }

        $qrCode = QrCode::format('svg')
            ->size(300)
            ->generate($this->unique_id);

        $filename = 'qrcodes/fundi_'.$this->id.'_'.time().'.svg';
        Storage::disk('public')->put($filename, $qrCode);

        $this->update(['qr_code_path' => $filename]);

        return $filename;
    }

    /**
     * Generate QR code without triggering model events.
     * Returns empty string and logs on failure so fundi creation is not aborted.
     */
    public function generateQrCodeQuietly(): string
    {
        if ($this->qr_code_path && Storage::disk('public')->exists($this->qr_code_path)) {
            return $this->qr_code_path;
        }

        try {
            $qrCode = QrCode::format('svg')
                ->size(300)
                ->generate($this->unique_id);

            $filename = 'qrcodes/fundi_'.$this->id.'_'.time().'.svg';
            Storage::disk('public')->put($filename, $qrCode);
            $this->updateQuietly(['qr_code_path' => $filename]);

            return $filename;
        } catch (\Exception $e) {
            Log::error('QR code generation failed', [
                'fundi_id' => $this->id,
                'unique_id' => $this->unique_id,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * Generate a collision-safe unique fundi ID (FND + 8 zero-padded digits).
     * Use this on every creation path so all fundis share one format.
     */
    public static function generateUniqueId(): string
    {
        do {
            $id = 'FND'.str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
        } while (static::where('unique_id', $id)->exists());

        return $id;
    }

    /**
     * Get the approval status based on most recent action
     * Compares timestamps to determine if approved or rejected status takes precedence
     *
     * The most recent action (approval or rejection) always takes precedence
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
        if ($rejectedTime > 0 && $this->rejected_by !== null) {
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
     * Check if fundi is approved
     */
    public function isApproved(): bool
    {
        return $this->approval_status === 'Approved';
    }

    /**
     * Check if fundi is rejected
     */
    public function isRejected(): bool
    {
        return $this->approval_status === 'Rejected';
    }

    /**
     * Check if fundi is pending
     */
    public function isPending(): bool
    {
        return $this->approval_status === 'Pending';
    }

    /**
     * Scope for approved fundis
     * Properly handles cases where rejection is more recent than approval
     */
    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at')
            ->whereNotNull('approved_by')
            ->where(function ($q) {
                // Either no rejection exists, or approval is more recent than rejection
                $q->where(function ($subQ) {
                    $subQ->whereNull('rejected_at')
                        ->whereNull('rejected_by');
                })
                    ->orWhere(function ($subQ) {
                        // Both exist, but approval is more recent
                        $subQ->whereNotNull('rejected_at')
                            ->whereRaw('approved_at > rejected_at');
                    });
            });
    }

    /**
     * Scope for rejected fundis
     * Properly handles cases where approval is more recent than rejection
     */
    public function scopeRejected($query)
    {
        return $query->whereNotNull('rejected_at')
            ->whereNotNull('rejected_by')
            ->where(function ($q) {
                // Either no approval exists, or rejection is more recent than approval
                $q->where(function ($subQ) {
                    $subQ->whereNull('approved_at')
                        ->whereNull('approved_by');
                })
                    ->orWhere(function ($subQ) {
                        // Both exist, but rejection is more recent
                        $subQ->whereNotNull('approved_at')
                            ->whereRaw('rejected_at > approved_at');
                    });
            });
    }

    /**
     * Scope for pending fundis
     * No approval or rejection has been made
     */
    public function scopePending($query)
    {
        return $query->where(function ($q) {
            // No approval
            $q->whereNull('approved_at')
                ->whereNull('approved_by');
        })->where(function ($q) {
            // No rejection
            $q->whereNull('rejected_at')
                ->whereNull('rejected_by');
        });
    }

    /**
     * Scope for active fundis
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for inactive fundis
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the ward that the fundi belongs to
     */
    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    /**
     * Get the user who created this fundi
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved this fundi
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who rejected this fundi
     */
    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Get site visits associated with this fundi
     */
    // Update the existing relationship to include pivot data
    public function siteVisits()
    {
        return $this->belongsToMany(SiteVisit::class, 'site_visit_fundis')
            ->withPivot('notes')
            ->withTimestamps();
    }

    /**
     * Get total quantity of products across all site visits
     */
    public function getTotalProductQuantityAttribute(): int
    {
        return SiteVisitProduct::whereIn(
            'site_visit_id',
            $this->siteVisits()->select('site_visits.id')
        )->sum('quantity');
    }

    // Add direct access to the pivot model if needed
    public function siteVisitFundis()
    {
        return $this->hasMany(SiteVisitFundi::class);
    }

    /**
     * Products recorded on site visits this fundi participated in.
     */
    public function siteVisitProducts()
    {
        return $this->hasManyThrough(
            SiteVisitProduct::class,
            SiteVisitFundi::class,
            'fundi_id',
            'site_visit_id',
            'id',
            'site_visit_id'
        );
    }

    /**
     * Eager-load visit count and bag totals (product_id = 1) for operator reports.
     */
    public function scopeWithMafundiReportStats($query)
    {
        return $query
            ->withCount('siteVisits')
            ->withSum(['siteVisitProducts as bags_total' => fn ($q) => $q->where('product_id', 1)], 'quantity');
    }

    /**
     * Row payload for operator mafundi PDF/CSV exports.
     */
    public function toMafundiReportRow(): array
    {
        return [
            'name' => $this->full_name,
            'gender' => $this->gender ? ucfirst($this->gender) : 'N/A',
            'primary_phone' => $this->primary_phone ?: 'N/A',
            'transaction_phone' => $this->transaction_phone ?: 'N/A',
            'address' => $this->address,
            'site_visits' => (int) ($this->site_visits_count ?? 0),
            'bags_total' => (int) ($this->bags_total ?? 0),
        ];
    }

    /**
     * Get transactions for this fundi
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get SMS messages sent to this fundi
     */
    public function smsMessages()
    {
        return $this->hasMany(SmsMessage::class, 'recipient_id')
            ->where('recipient_type', 'fundi');
    }

    /**
     * Get the district through ward relationship
     */
    public function district()
    {
        return $this->hasOneThrough(
            District::class,
            Ward::class,
            'id',           // Foreign key on ward table
            'id',           // Foreign key on district table
            'ward_id',      // Local key on fundi table
            'district_id'   // Local key on ward table
        );
    }

    /**
     * Get the region through ward and district relationships
     */
    public function region()
    {
        return $this->hasOneThrough(
            Region::class,
            District::class,
            'id',           // Foreign key on district table
            'id',           // Foreign key on region table
            'ward_id',      // Local key on fundi table (via ward)
            'region_id'     // Local key on district table
        )->join('wards', 'wards.district_id', '=', 'districts.id')
            ->where('wards.id', $this->ward_id);
    }

    // ==================== CLOUDINARY IMAGE HELPERS ====================

    /**
     * Get thumbnail version of profile photo
     */
    public function getProfilePhotoThumbnailAttribute(): ?string
    {
        if ($this->profile_photo_public_id) {
            return Cloudinary::url($this->profile_photo_public_id, [
                'width' => 150,
                'height' => 150,
                'crop' => 'fill',
                'quality' => 'auto',
                'fetch_format' => 'auto',
            ]);
        }

        return null;
    }

    /**
     * Get avatar version of profile photo
     */
    public function getProfilePhotoAvatarAttribute(): ?string
    {
        if ($this->profile_photo_public_id) {
            return Cloudinary::url($this->profile_photo_public_id, [
                'width' => 50,
                'height' => 50,
                'crop' => 'fill',
                'quality' => 'auto',
                'fetch_format' => 'auto',
            ]);
        }

        return null;
    }

    /**
     * Get full URL to QR code
     */
    public function getQrCodeUrlAttribute(): ?string
    {
        if ($this->qr_code_path) {
            return Storage::url($this->qr_code_path);
        }

        return null;
    }
}
