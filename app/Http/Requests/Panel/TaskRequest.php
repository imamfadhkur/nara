<?php

namespace App\Http\Requests\Panel;

use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        if ($this->boolean('_inline')) {
            return [
                'status' => ['sometimes', Rule::enum(TaskStatus::class)],
                'waiting_reason' => [
                    Rule::requiredIf(fn () => $this->input('status') === TaskStatus::Waiting->value),
                    'nullable',
                    'string',
                    'max:255',
                ],
                'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
                'due_date' => ['sometimes', 'nullable', 'date'],
                'estimated_minutes' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1440'],
                'actual_minutes' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:1440'],
            ];
        }

        return [
            'project_id' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')
                    ->where('user_id', $this->user()?->id)
                    ->whereNull('deleted_at'),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'waiting_reason' => [
                Rule::requiredIf(fn () => $this->input('status') === TaskStatus::Waiting->value),
                'nullable',
                'string',
                'max:255',
            ],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'estimated_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'actual_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'due_date' => ['nullable', 'date'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'project_id.required' => 'Pilih project wadah task ini.',
            'project_id.exists' => 'Project yang dipilih tidak ditemukan.',
            'title.required' => 'Judul task wajib diisi.',
            'waiting_reason.required' => 'Status "Menunggu" wajib disertai alasan menunggu.',
            'estimated_minutes.min' => 'Estimasi minimal 1 menit.',
            'estimated_minutes.max' => 'Estimasi maksimal 1440 menit (24 jam).',
        ];
    }
}

