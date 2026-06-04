<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisbursementBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_allocation_id',
        'title',
        'description',
        'total_amount',
        'disbursement_date',
        'status',
        'created_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'disbursement_date' => 'datetime',
    ];

    public function budgetAllocation()
    {
        return $this->belongsTo(BudgetAllocation::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
