<?php

namespace App\View\Components;

use BackedEnum;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Lencana status: selalu warna + label teks, tidak pernah warna saja (Bagian 5.5).
 */
class StatusBadge extends Component
{
    public string $label;

    public string $tone;

    public function __construct(
        BackedEnum|string|null $status = null,
        ?string $label = null,
        ?string $tone = null,
    ) {
        if ($status instanceof BackedEnum) {
            $this->label = $label ?? (method_exists($status, 'label') ? $status->label() : $status->value);
            $this->tone = $tone ?? (method_exists($status, 'tone') ? $status->tone() : 'neutral');
        } else {
            $this->label = $label ?? (string) $status;
            $this->tone = $tone ?? 'neutral';
        }
    }

    public function classes(): string
    {
        return match ($this->tone) {
            'primary' => 'bg-sky-50 text-sky-800 ring-sky-600/20',
            'accent' => 'bg-violet-50 text-violet-800 ring-violet-600/20',
            'success' => 'bg-emerald-50 text-emerald-800 ring-emerald-600/20',
            'warning' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
            'danger' => 'bg-rose-50 text-rose-800 ring-rose-600/20',
            'muted' => 'bg-slate-100 text-slate-500 ring-slate-400/20 line-through',
            default => 'bg-slate-100 text-slate-700 ring-slate-500/20',
        };
    }

    public function dotClass(): string
    {
        return match ($this->tone) {
            'primary' => 'bg-sky-600',
            'accent' => 'bg-violet-600',
            'success' => 'bg-emerald-600',
            'warning' => 'bg-amber-500',
            'danger' => 'bg-rose-600',
            'muted' => 'bg-slate-400',
            default => 'bg-slate-500',
        };
    }

    public function render(): View
    {
        return view('components.status-badge');
    }
}

