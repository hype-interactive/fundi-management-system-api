<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SiteVisitListResource extends BaseResource
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
            'site_name' => $this->site_name,
            'visit_date' => $this->formatDate($this->visit_date),
            'ward_name' => $this->ward->name ?? null,
            'status' => $this->status,
            'photos_count' => $this->photos_count ?? 0,
            'fundis_count' => $this->fundis_count ?? 0,
            'created_at' => $this->formatTimestamp($this->created_at),
        ];
    }
}
