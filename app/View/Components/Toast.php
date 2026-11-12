<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Toast extends Component
{
    public ?string $message = null;

    public string $type = 'info';

    public function __construct(string|array|null $message = null, string $type = 'info')
    {
        $sessionToast = session('toast');
        $sessionWarning = session('warning');

        if (is_array($message)) {
            $this->message = isset($message['message']) ? (string) $message['message'] : (isset($message['text']) ? (string) $message['text'] : null);
            $this->type = isset($message['type']) ? (string) $message['type'] : $type;
        } elseif (is_string($message)) {
            $this->message = $message;
            $this->type = $type;
        } elseif (is_array($sessionToast)) {
            $this->message = isset($sessionToast['message']) ? (string) $sessionToast['message'] : (isset($sessionToast['text']) ? (string) $sessionToast['text'] : null);
            $this->type = isset($sessionToast['type']) ? (string) $sessionToast['type'] : (session()->has('warning') ? 'warning' : $type);
        } elseif (is_string($sessionToast)) {
            $this->message = $sessionToast;
            $this->type = session()->has('warning') ? 'warning' : $type;
        } elseif (session()->has('warning') && is_string($sessionWarning)) {
            $this->message = $sessionWarning;
            $this->type = 'warning';
        } else {
            $this->message = null;
            $this->type = $type;
        }

        if (session()->has('warning') && ! is_array($sessionToast) && ! is_array($message)) {
            $this->type = 'warning';
        }
    }

    public function render(): View
    {
        return view('components.toast');
    }
}

