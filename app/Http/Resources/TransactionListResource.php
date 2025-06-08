<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TransactionListResource extends BaseResource
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
            'status' => $this->status,
            'fundi_name' => $this->fundi->full_name ?? null,
            'fundi_phone' => $this->fundi->transaction_phone ?? null,
            'processed_at' => $this->formatTimestamp($this->processed_at),
        ];
    }
}
