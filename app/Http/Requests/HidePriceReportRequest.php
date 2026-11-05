<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HidePriceReportRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'reason' => ['description' => 'Why the report was hidden. Shown to admins only.', 'example' => 'Price entered per sack instead of per kg.'],
        ];
    }
}
