<?php

namespace App\Http\Resources;

use App\Models\PriceReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PriceReport
 */
class PriceReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'price' => (float) $this->price,
            'srp_price' => $this->srp_price === null ? null : (float) $this->srp_price,
            'flagged' => $this->is_flagged,
            'reported_at' => $this->reported_at->toIso8601ZuluString(),
            'commodity' => CommodityResource::make($this->whenLoaded('commodity')),
            'market' => MarketResource::make($this->whenLoaded('market')),
            'user_id' => $this->user_id,
            'hidden_at' => $this->hidden_at?->toIso8601ZuluString(),
            'hidden_reason' => $this->when($this->hidden_at !== null, $this->hidden_reason),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
        ];
    }
}
