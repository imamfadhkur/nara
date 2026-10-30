<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FormField extends Component
{
    public function __construct(
        public string $name,
        public string $label,
        public bool $required = false,
        public ?string $hint = null,
    ) {}

    public function render(): View
    {
        return view('components.form-field');
    }
}

