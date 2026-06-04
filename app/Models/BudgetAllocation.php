<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'total_amount',
        'remaining_amount',
        'created_by',
        'status',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function disbursementBatches()
    {
        return $this->hasMany(DisbursementBatch::class);
    }
}
