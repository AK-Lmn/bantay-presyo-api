<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportSrpsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:1024'],
            'effective_date' => ['sometimes', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'file' => ['description' => 'CSV with a header row: `commodity`, `price`, and optionally `unit` and `effective_date`. Max 1 MB.'],
            'effective_date' => ['description' => 'Used for rows without an `effective_date`.', 'example' => '2026-11-01'],
        ];
    }
}
