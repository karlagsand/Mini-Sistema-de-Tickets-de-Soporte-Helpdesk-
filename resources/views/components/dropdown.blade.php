<a {{ $attributes->merge(['class' => 'block w-full px-4 py-2 text-start text-sm leading-5 text-[var(--text-main)] hover:bg-slate-50 focus:outline-none focus:bg-slate-50 transition duration-150 ease-in-out']) }}>
    {{ $slot }}
</a>