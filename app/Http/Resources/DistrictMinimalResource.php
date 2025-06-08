<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class DistrictMinimalResource extends BaseResource
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
            'name' => $this->name,
            'region' => new RegionMinimalResource($this->whenLoaded('region')),
        ];
    }
}
