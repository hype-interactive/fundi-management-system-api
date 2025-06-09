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
            'photo_path' => $this->photo_path,
            'photo_url' => asset('storage/' . $this->photo_path), // Adjust based on your storage setup
        ];
    }
}
