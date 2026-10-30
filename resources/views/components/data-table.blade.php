<div class="nara-card overflow-hidden">
    @isset($filters)
        <div class="px-4 py-3 bg-slate-50/70 border-b border-slate-200">
            {{ $filters }}
        </div>
    @endisset

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            @if ($columns !== [])
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-600">
                    <tr>
                        @foreach ($columns as $key => $col)
                            @php
                                $meta = is_array($col) ? $col : ['label' => $col];
                                $sortable = $meta['sortable'] ?? false;
                                $align = ($meta['align'] ?? 'left') === 'right' ? 'text-right' : 'text-left';
                                $isCurrent = $sort === $key;
                                $nextDir = ($isCurrent && $dir === 'asc') ? 'desc' : 'asc';
                            @endphp
                            <th scope="col" class="px-4 py-3 {{ $align }}">
                                @if ($sortable)
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => $key, 'dir' => $nextDir]) }}"
                                       class="inline-flex items-center gap-1 hover:text-sky-700">
                                        <span>{{ $meta['label'] }}</span>
                                        @if ($isCurrent)
                                            <span aria-hidden="true">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                                        @endif
                                    </a>
                                @else
                                    {{ $meta['label'] }}
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            <tbody class="divide-y divide-slate-100 bg-white">
                @if (trim($slot) !== '')
                    {{ $slot }}
                @else
                    <tr>
                        <td colspan="{{ max(1, count($columns)) }}" class="px-4 py-8 text-center text-slate-500">
                            {{ $empty }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    @if ($paginator && $paginator->hasPages())
        <div class="px-4 py-3 border-t border-slate-200 bg-slate-50/50">
            {{ $paginator->links() }}
        </div>
    @endif
</div>

