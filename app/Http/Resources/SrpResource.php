<?php

namespace App\Http\Resources;

use App\Models\Srp;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Srp
 */
class SrpResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'commodity_id' => $this->commodity_id,
            'price' => (float) $this->price,
            'effective_date' => $this->effective_date->toDateString(),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
        ];
    }
}
