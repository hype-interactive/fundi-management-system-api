<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OperatorIncentive extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator_id',
        'site_visit_id',
        'amount',
        'description',
        'status',
        'approved_by',
        'approved_at',
        'paid_at',
        'transaction_reference',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function siteVisit()
    {
        return $this->belongsTo(SiteVisit::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
