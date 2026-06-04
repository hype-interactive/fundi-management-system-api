<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'disbursement_batch_id',
        'fundi_id',
        'amount',
        'transaction_reference',
        'status',
        'mno_response',
        'failure_reason',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function disbursementBatch()
    {
        return $this->belongsTo(DisbursementBatch::class);
    }

    public function fundi()
    {
        return $this->belongsTo(Fundi::class);
    }
}
