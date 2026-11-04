<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMarketRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $market = $this->route('market');
        $required = $this->isMethod('post') ? 'required' : 'sometimes';
        $city = $this->input('city', $market?->city);

        return [
            'name' => [
                $required, 'string', 'max:255',
                Rule::unique('markets', 'name')->where('city', $city)->ignore($market),
            ],
            'city' => [$required, 'string', 'max:100'],
            'region' => [$required, 'string', 'max:100'],
        ];
    }
}
