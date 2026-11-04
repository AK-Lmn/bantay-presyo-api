<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePriceReportRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'commodity_id' => ['required', 'integer', 'exists:commodities,id'],
            'market_id' => ['required', 'integer', 'exists:markets,id'],
            'price' => ['required', 'numeric', 'gt:0', 'max:100000', 'decimal:0,2'],
            'reported_at' => ['sometimes', 'date', 'before_or_equal:now'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'commodity_id' => ['example' => 1],
            'market_id' => ['example' => 1],
            'price' => ['description' => 'Price in PHP per commodity unit.', 'example' => 52.5],
            'reported_at' => ['description' => 'When the price was seen. Defaults to now.', 'example' => now()->subHour()->toIso8601ZuluString()],
        ];
    }
}
