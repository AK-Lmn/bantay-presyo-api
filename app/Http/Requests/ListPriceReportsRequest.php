<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPriceReportsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'commodity_id' => ['sometimes', 'integer'],
            'market_id' => ['sometimes', 'integer'],
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'date_format:Y-m-d', Rule::when($this->filled('from'), 'after_or_equal:from')],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function queryParameters(): array
    {
        return [
            'commodity_id' => ['description' => 'Filter by commodity.', 'example' => 1],
            'market_id' => ['description' => 'Filter by market.', 'example' => 1],
            'from' => ['description' => 'Earliest report date (inclusive).', 'example' => now()->subDays(6)->toDateString()],
            'to' => ['description' => 'Latest report date (inclusive).', 'example' => now()->toDateString()],
            'page' => ['example' => 1],
            'per_page' => ['description' => 'Max 100.', 'example' => 20],
        ];
    }
}
