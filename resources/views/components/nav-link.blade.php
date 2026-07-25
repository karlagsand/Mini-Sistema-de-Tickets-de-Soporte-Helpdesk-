@props(['active'])

@php
$classes = ($active ?? false)
    ? 'inline-flex items-center px-1 pt-1 border-b-2 border-[var(--primary)] text-sm font-semibold leading-5 text-[var(--text-main)] focus:outline-none transition duration-150 ease-in-out'
    : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-[var(--text-soft)] hover:text-[var(--text-main)] hover:border-[var(--border-strong)] focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>