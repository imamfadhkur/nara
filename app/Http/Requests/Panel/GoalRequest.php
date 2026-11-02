<?php

namespace App\Http\Requests\Panel;

use App\Support\Enums\GoalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('wip_limit') === '') {
            $this->merge(['wip_limit' => null]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'life_domain_id' => [
                'required',
                'integer',
                Rule::exists('life_domains', 'id')->where('user_id', $this->user()?->id),
            ],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'target_date' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(GoalStatus::class)],
            'wip_limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'life_domain_id.required' => 'Pilih Life Domain induk.',
            'life_domain_id.exists' => 'Life Domain tidak ditemukan.',
            'title.required' => 'Judul goal wajib diisi.',
            'target_date.date' => 'Format tanggal target tidak valid.',
            'status.enum' => 'Status goal tidak valid.',
        ];
    }
}

