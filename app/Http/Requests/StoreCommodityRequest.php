<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommodityRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $commodity = $this->route('commodity');
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:255', Rule::unique('commodities', 'name')->ignore($commodity)],
            'category' => [$required, 'string', 'max:100'],
            'unit' => [$required, 'string', 'max:20'],
        ];
    }
}
