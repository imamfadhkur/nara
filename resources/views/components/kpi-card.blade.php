<div {{ $attributes->merge(['class' => 'nara-card p-4 '.$borderClass()]) }}>
    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $title }}</div>
    <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $value }}</div>
    @if ($subtitle)
        <div class="mt-1 text-xs text-slate-600">{{ $subtitle }}</div>
    @endif
    @if (trim($slot) !== '')
        <div class="mt-2 text-xs text-slate-600">{{ $slot }}</div>
    @endif
</div>

