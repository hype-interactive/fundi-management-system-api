<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class FundiResource extends BaseResource
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
            'unique_id' => $this->unique_id,
            'name' => $this->full_name,
            'gender' => $this->gender,
            'nida_number' => $this->nida_number,
            'phones' => [
                'primary' => $this->primary_phone,
                'transaction' => $this->transaction_phone,
            ],
            'ward' => new WardMinimalResource($this->whenLoaded('ward')),
            'ranking' => $this->ranking,
            'profile_photo' => $this->profile_photo,
            'status' => $this->status,
            'approval_status' => $this->approval_status,
            'legacy_match' => $this->legacy_match,
            'created_by' => new UserMinimalResource($this->whenLoaded('creator')),
            'approved_by' => new UserMinimalResource($this->whenLoaded('approver')),
            'approved_at' => $this->formatTimestamp($this->approved_at),
            'rejection_reason' => $this->when($this->rejected_at, $this->rejection_reason),
            'created_at' => $this->formatTimestamp($this->created_at),

            // Extended data when requested
            'transaction_summary' => $this->when(
                $request->include_transactions ?? false,
                [
                    'total_amount' => $this->formatAmount($this->transactions_sum_amount ?? 0),
                    'transaction_count' => $this->transactions_count ?? 0,
                    'last_transaction_date' => $this->formatTimestamp($this->last_transaction_date ?? null),
                ]
            ),
        ];
    }
}
