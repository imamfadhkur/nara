<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PanelLayout extends Component
{
    public function __construct(
        public ?string $title = null,
        public ?string $heading = null,
    ) {}

    public function render(): View
    {
        return view('layouts.panel');
    }
}

