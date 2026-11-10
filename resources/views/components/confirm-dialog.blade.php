<div x-data="{ open: false }" class="inline-block">
    <span @click="open = true">
        {{ $slot }}
    </span>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            @keydown.escape.window="open = false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            role="dialog"
            aria-modal="true"
        >
            <div @click.outside="open = false" class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl border border-slate-200">
                <h3 class="text-base font-semibold text-slate-900">{{ $title }}</h3>
                <p class="mt-2 text-sm text-slate-600">{{ $message }}</p>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="open = false" class="nara-btn-secondary">Batal</button>
                    <form method="POST" action="{{ $action }}">
                        @csrf
                        @if (strtoupper($method) !== 'POST')
                            @method($method)
                        @endif
                        <button type="submit" class="nara-btn-danger">{{ $confirmLabel }}</button>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>

