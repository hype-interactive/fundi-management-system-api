<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SiteVisitResource extends BaseResource
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
            'site_description' => $this->site_description,
            'visit_date' => $this->formatDate($this->visit_date),
            'location' => [
                'ward' => new WardMinimalResource($this->whenLoaded('ward')),
                'gps' => [
                    'latitude' => $this->gps_latitude,
                    'longitude' => $this->gps_longitude,
                    'formatted' => $this->location,
                ],
            ],
            'status' => $this->status,
            'operator' => new UserMinimalResource($this->whenLoaded('operator')),
            'approved_by' => new UserMinimalResource($this->whenLoaded('approver')),
            'approved_at' => $this->formatTimestamp($this->approved_at),
            'rejection_reason' => $this->when($this->status === 'rejected', $this->rejection_reason),
            'created_at' => $this->formatTimestamp($this->created_at),

            // Related data
            'photos' => SiteVisitPhotoResource::collection($this->whenLoaded('photos')),
            'products' => $this->when(
                $this->relationLoaded('products'),
                $this->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'quantity' => $product->pivot->quantity,
                        'notes' => $product->pivot->notes,
                    ];
                })
            ),
            'fundis' => $this->when(
                $this->relationLoaded('fundis'),
                $this->fundis->map(function ($fundi) {
                    return [
                        'id' => $fundi->id,
                        'unique_id' => $fundi->unique_id,
                        'name' => $fundi->full_name,
                        'phone' => $fundi->primary_phone,
                        'notes' => $fundi->pivot->notes,
                    ];
                })
            ),

            // Summary counts
            'summary' => [
                'photos_count' => $this->photos_count ?? 0,
                'products_count' => $this->products_count ?? 0,
                'fundis_count' => $this->fundis_count ?? 0,
            ],
        ];
    }
}
