<?php

namespace App\Http\Requests\Panel;

use App\Models\LifeDomain;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LifeDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'color' => ['required', Rule::in(array_keys(LifeDomain::COLORS))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama Life Domain wajib diisi.',
            'name.max' => 'Nama Life Domain maksimal 120 karakter.',
            'color.required' => 'Pilih warna domain.',
            'color.in' => 'Warna yang dipilih tidak valid.',
        ];
    }
}

