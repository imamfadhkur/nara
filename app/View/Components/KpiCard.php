<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class KpiCard extends Component
{
    public function __construct(
        public string $title,
        public string $value,
        public ?string $subtitle = null,
        public string $tone = 'neutral',
    ) {}

    public function borderClass(): string
    {
        return match ($this->tone) {
            'primary' => 'border-l-4 border-l-sky-600',
            'accent' => 'border-l-4 border-l-violet-600',
            'success' => 'border-l-4 border-l-emerald-600',
            'warning' => 'border-l-4 border-l-amber-500',
            'danger' => 'border-l-4 border-l-rose-600',
            default => 'border-l-4 border-l-slate-400',
        };
    }

    public function render(): View
    {
        return view('components.kpi-card');
    }
}

