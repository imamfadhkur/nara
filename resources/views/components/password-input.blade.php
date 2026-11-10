@props([
    'disabled' => false,
    'id' => null,
    'name' => 'password',
    'value' => null,
    'required' => false,
    'autocomplete' => 'current-password',
    'placeholder' => null,
    'inputClass' => 'nara-input',
])

<div x-data="{ show: false }" class="relative w-full">
    <input
        :type="show ? 'text' : 'password'"
        id="{{ $id ?? $name }}"
        name="{{ $name }}"
        value="{{ $value ?? old($name) }}"
        @disabled($disabled)
        @required($required)
        autocomplete="{{ $autocomplete }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        {{ $attributes->merge(['class' => $inputClass . ' pr-10']) }}
    >
    <button
        type="button"
        @click="show = !show"
        tabindex="-1"
        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none transition cursor-pointer"
        :title="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
        :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
    >
        {{-- Ikon Mata (klik untuk melihat) --}}
        <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
        {{-- Ikon Mata Tercoret (klik untuk sembunyikan) --}}
        <svg x-show="show" x-cloak class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
        </svg>
    </button>
</div>

