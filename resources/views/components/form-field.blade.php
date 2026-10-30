<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-slate-700 mb-1">
        {{ $label }}
        @if ($required)
            <span class="text-rose-600" aria-label="wajib">*</span>
        @endif
    </label>

    {{ $slot }}

    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-xs font-medium text-rose-600" role="alert">{{ $message }}</p>
    @enderror
</div>

