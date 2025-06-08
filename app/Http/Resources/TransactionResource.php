<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TransactionResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->formatAmount($this->amount),
            'transaction_reference' => $this->transaction_reference,
            'status' => $this->status,
            'failure_reason' => $this->when($this->status === 'failed', $this->failure_reason),
            'processed_at' => $this->formatTimestamp($this->processed_at),
            'created_at' => $this->formatTimestamp($this->created_at),

            // Related data
            'fundi' => $this->when(
                $this->relationLoaded('fundi'),
                [
                    'id' => $this->fundi->id,
                    'unique_id' => $this->fundi->unique_id,
                    'name' => $this->fundi->full_name,
                    'phone' => $this->fundi->transaction_phone,
                    'ward' => $this->fundi->ward->name ?? null,
                ]
            ),

            'disbursement_batch' => $this->when(
                $this->relationLoaded('disbursementBatch'),
                [
                    'id' => $this->disbursementBatch->id,
                    'title' => $this->disbursementBatch->title,
                    'disbursement_date' => $this->formatTimestamp($this->disbursementBatch->disbursement_date),
                ]
            ),

            // MNO response (mobile network operator response)
            'mno_response' => $this->when(
                $request->include_mno_response ?? false,
                $this->mno_response ? json_decode($this->mno_response, true) : null
            ),
        ];
    }
}
