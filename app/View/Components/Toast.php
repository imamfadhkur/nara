<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Toast extends Component
{
    public ?string $message;

    public string $type;

    public function __construct(?string $message = null, string $type = 'info')
    {
        $this->message = $message ?? session('toast') ?? session('warning');
        $this->type = session()->has('warning') ? 'warning' : $type;
    }

    public function render(): View
    {
        return view('components.toast');
    }
}

