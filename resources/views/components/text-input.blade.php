@props(['disabled' => false])

<input
    @disabled($disabled)
    {{ $attributes->merge([
        'class' => 'block w-full rounded-2xl border border-[var(--border-strong)] bg-white px-4 py-3 text-[var(--text-main)] shadow-sm placeholder:text-[var(--text-muted)] focus:border-[var(--primary)] focus:ring-4 focus:ring-[rgba(109,74,255,0.12)]'
    ]) }}
>