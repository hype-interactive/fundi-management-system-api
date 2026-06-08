<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OperatorProfileResource extends BaseResource
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
            'name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'role' => $this->role->name ?? null,
            'profile_photo' => [
                'thumbnail' => $this->profile_photo_thumbnail,
                'avatar' => $this->profile_photo_avatar,
                'original' => $this->profile_photo_url,
            ],
            'has_all_district_access' => isRoot() || isAdministrator(),
            'primary_district' => new DistrictMinimalResource($this->whenLoaded('districts', $this->primary_district)),
            'districts' => DistrictMinimalResource::collection($this->whenLoaded('districts')),
            'stats' => $this->when(
                $request->include_stats ?? false,
                [
                    'total_fundis_registered' => $this->created_fundis_count ?? 0,
                    'pending_fundis' => $this->pending_fundis_count ?? 0,
                    'total_site_visits' => $this->site_visits_count ?? 0,
                    'pending_site_visits' => $this->pending_site_visits_count ?? 0,
                ]
            ),
        ];
    }
}
