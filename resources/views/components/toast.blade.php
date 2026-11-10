@if ($message)
    <div
        x-data="{ open: true }"
        x-init="setTimeout(() => open = false, 4500)"
        x-show="open"
        x-transition.opacity
        role="status"
        class="mb-4 flex items-center justify-between gap-3 rounded-lg border px-4 py-3 text-sm shadow-sm {{ $type === 'warning' ? 'border-amber-300 bg-amber-50 text-amber-900' : 'border-sky-200 bg-sky-50 text-sky-900' }}"
    >
        <span>{{ $message }}</span>
        <button type="button" @click="open = false" class="text-xs font-medium underline opacity-75 hover:opacity-100">
            Tutup
        </button>
    </div>
@endif

