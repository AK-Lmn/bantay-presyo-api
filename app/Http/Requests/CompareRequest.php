<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompareRequest extends FormRequest
{
    public const DEFAULT_DAYS = 7;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'days' => ['sometimes', 'integer', 'min:1', 'max:90'],
            'market_id' => ['sometimes', 'integer', 'exists:markets,id'],
        ];
    }

    public function days(): int
    {
        return $this->integer('days', self::DEFAULT_DAYS);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function queryParameters(): array
    {
        return [
            'days' => ['description' => 'Size of the averaging window in days, ending today.', 'example' => 7],
            'market_id' => ['description' => 'Only count reports from this market.', 'example' => 1],
        ];
    }
}
