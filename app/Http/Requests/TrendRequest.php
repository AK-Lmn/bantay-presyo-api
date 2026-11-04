<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TrendRequest extends FormRequest
{
    public const DEFAULT_DAYS = 30;

    public const MAX_DAYS = 366;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'date_format:Y-m-d', Rule::when($this->filled('from'), 'after_or_equal:from')],
            'market_id' => ['sometimes', 'integer', 'exists:markets,id'],
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($this->from()->diffInDays($this->to()) >= self::MAX_DAYS) {
                    $validator->errors()->add('from', 'The date range may not exceed '.self::MAX_DAYS.' days.');
                }
            },
        ];
    }

    public function to(): Carbon
    {
        return $this->filled('to')
            ? Carbon::createFromFormat('Y-m-d', $this->string('to')->value())->startOfDay()
            : now()->startOfDay();
    }

    public function from(): Carbon
    {
        return $this->filled('from')
            ? Carbon::createFromFormat('Y-m-d', $this->string('from')->value())->startOfDay()
            : $this->to()->subDays(self::DEFAULT_DAYS - 1);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function queryParameters(): array
    {
        return [
            'from' => ['description' => 'Start date (inclusive). Defaults to 29 days before `to`.', 'example' => now()->subDays(6)->toDateString()],
            'to' => ['description' => 'End date (inclusive). Defaults to today.', 'example' => now()->toDateString()],
            'market_id' => ['description' => 'Only count reports from this market.', 'example' => 1],
        ];
    }
}
