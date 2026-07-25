@props(['messages'])

@php
    if ($messages instanceof \Illuminate\Support\MessageBag) {
        $messages = $messages->all();
    } elseif (is_string($messages)) {
        $messages = [$messages];
    } else {
        $messages = collect($messages ?? [])
            ->flatten()
            ->filter(fn ($message) => is_string($message) && trim($message) !== '')
            ->values()
            ->all();
    }
@endphp

@if (! empty($messages))
    <ul {{ $attributes->merge(['class' => 'mt-2 space-y-1']) }}>
        @foreach ($messages as $message)
            <li class="text-sm font-medium text-[var(--error-text)]">
                {{ $message }}
            </li>
        @endforeach
    </ul>
@endif