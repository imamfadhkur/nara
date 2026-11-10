<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/** Dialog konfirmasi untuk aksi destruktif (Bagian 5.5). */
class ConfirmDialog extends Component
{
    public function __construct(
        public string $action,
        public string $title = 'Konfirmasi Penghapusan',
        public string $message = 'Tindakan ini akan dicatat di riwayat aktivitas. Lanjutkan?',
        public string $confirmLabel = 'Ya, Hapus',
        public string $method = 'DELETE',
    ) {}

    public function render(): View
    {
        return view('components.confirm-dialog');
    }
}

