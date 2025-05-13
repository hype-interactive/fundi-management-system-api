<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'fname',
        'mname',
        'lname',
        'email',
        'phone',
        'role_id',
        'status',
        'profile_photo',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function districts()
    {
        return $this->belongsToMany(District::class, 'operator_districts');
    }

    public function siteVisits()
    {
        return $this->hasMany(SiteVisit::class, 'operator_id');
    }

    public function approvedSiteVisits()
    {
        return $this->hasMany(SiteVisit::class, 'approved_by');
    }

    public function createdFundis()
    {
        return $this->hasMany(Fundi::class, 'created_by');
    }

    public function approvedFundis()
    {
        return $this->hasMany(Fundi::class, 'approved_by');
    }

    public function budgetAllocations()
    {
        return $this->hasMany(BudgetAllocation::class, 'created_by');
    }

    public function disbursementBatches()
    {
        return $this->hasMany(DisbursementBatch::class, 'created_by');
    }

    public function smsCampaigns()
    {
        return $this->hasMany(SmsCampaign::class, 'created_by');
    }

    public function smsTemplates()
    {
        return $this->hasMany(SmsTemplate::class, 'created_by');
    }

    // public function legacyImports()
    // {
    //     return $this->hasMany(LegacyImport::class, 'imported_by');
    // }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function incentives()
    {
        return $this->hasMany(OperatorIncentive::class, 'operator_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function getFullNameAttribute()
    {
        return trim("{$this->fname} {$this->mname} {$this->lname}");
    }

    // Because of the deviation from using the default 'name' as required by the Laravel's default User model, replace the default 'name' with 'full_name' in the User model.
    public function getNameAttribute()
    {
        return $this->full_name;
    }
}
