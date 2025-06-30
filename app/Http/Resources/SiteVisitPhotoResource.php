<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SiteVisitPhotoResource extends BaseResource
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
            'photo_url' => $this->photo_url,
            'photo_public_id' => $this->photo_public_id,
        ];
    }
}
