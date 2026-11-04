<?php

namespace App\Http\Requests\Panel;

use App\Support\Enums\Level;
use App\Support\Enums\ProjectCategory;
use App\Support\Enums\ProjectOrigin;
use App\Support\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('goal_id') === '') {
            $this->merge(['goal_id' => null]);
        }
        if ($this->input('wip_slot') === '') {
            $this->merge(['wip_slot' => null]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $domainIds = $this->user()?->lifeDomains()->pluck('id') ?? collect();

        return [
            'goal_id' => [
                'nullable',
                'integer',
                Rule::exists('goals', 'id')->whereIn('life_domain_id', $domainIds),
            ],
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', Rule::enum(ProjectCategory::class)],
            'origin' => ['required', Rule::enum(ProjectOrigin::class)],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'wip_slot' => [
                Rule::requiredIf(fn () => $this->input('status') === ProjectStatus::Active->value && empty($this->input('goal_id'))),
                'nullable',
                'string',
                'max:64',
            ],
            'impact' => ['required', Rule::enum(Level::class)],
            'urgency' => ['required', Rule::enum(Level::class)],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'definition_of_done' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama project wajib diisi.',
            'wip_slot.required' => 'Project aktif wajib memilih Goal (atau slot WIP).',
            'due_date.after_or_equal' => 'Tanggal tenggat tidak boleh sebelum tanggal mulai.',
            'goal_id.exists' => 'Goal yang dipilih tidak valid.',
        ];
    }
}

