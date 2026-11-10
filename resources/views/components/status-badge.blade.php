<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset '.$classes()]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $dotClass() }}" aria-hidden="true"></span>
    <span>{{ $label }}</span>
</span>

