<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class FundiMinimalResource extends BaseResource
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
            'primary_phone' => $this->primary_phone,
            'ward_name' => $this->ward->name ?? null,
            'status' => $this->status,
            'approval_status' => $this->approval_status,
        ];
    }
}
