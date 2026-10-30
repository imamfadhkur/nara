<?php

namespace App\View\Components;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Tabel data native (Bagian 3.7): header kolom dapat diurutkan, baris saring,
 * keadaan kosong, dan paginasi server-side.
 */
class DataTable extends Component
{
    /**
     * @param  array<string, string|array{label: string, sortable?: bool, align?: string}>  $columns
     */
    public function __construct(
        public array $columns = [],
        public ?LengthAwarePaginator $paginator = null,
        public ?string $sort = null,
        public string $dir = 'asc',
        public string $empty = 'Belum ada data.',
    ) {}

    public function render(): View
    {
        return view('components.data-table');
    }
}

