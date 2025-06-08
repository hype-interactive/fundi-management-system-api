<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Base Resource for common functionality
 */
abstract class BaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }

    /**
     * Common timestamp formatting
     */
    protected function formatTimestamp($timestamp): ?string
    {
        return $timestamp ? $timestamp->format('Y-m-d H:i:s') : null;
    }

    /**
     * Common date formatting
     */
    protected function formatDate($date): ?string
    {
        return $date ? $date->format('Y-m-d') : null;
    }

    /**
     * Format amount to 2 decimal places
     */
    protected function formatAmount($amount): ?string
    {
        return $amount ? number_format($amount, 2) : null;
    }
}
