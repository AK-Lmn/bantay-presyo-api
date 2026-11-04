<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSrpRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'commodity_id' => ['required', 'integer', 'exists:commodities,id'],
            'price' => ['required', 'numeric', 'gt:0', 'max:100000', 'decimal:0,2'],
            'effective_date' => [
                'required', 'date_format:Y-m-d',
                Rule::unique('srps')->where('commodity_id', $this->input('commodity_id')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'effective_date.unique' => 'An SRP already exists for this commodity on that date.',
        ];
    }
}
