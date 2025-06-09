<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserMinimalResource extends BaseResource
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
            'role' => $this->role->name ?? null,
        ];
    }
}
