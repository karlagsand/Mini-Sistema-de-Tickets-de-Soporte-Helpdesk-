@php
    $internalNotifications = $internalNotifications ?? collect();
    $unreadNotifications = $unreadNotifications ?? $internalNotifications->count();
    $notificationSignature = $internalNotifications->pluck('id')->implode('-') . ':' . $unreadNotifications;
@endphp

<div data-notification-panel data-notification-signature="{{ $notificationSignature }}" class="hidden"></div>

@if($internalNotifications->count() > 0)
    <section class="app-card p-6 space-y-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="section-title">Novedades importantes</h2>
                <p class="text-soft text-sm">Actualizaciones pendientes de revisar.</p>
            </div>
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="app-btn-secondary text-sm">Marcar todas como leídas</button>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($internalNotifications as $notification)
                @php
                    $typeClass = match($notification->type) {
                        'success' => 'badge-emerald',
                        'warning' => 'badge-amber',
                        'danger' => 'badge-rose',
                        default => 'badge-blue',
                    };
                @endphp
                <div class="info-panel">
                    <div class="flex items-start justify-between gap-3">
                        <span class="app-badge {{ $typeClass }}">Nueva</span>
                        <span class="text-xs text-soft">
                            {{ class_exists('App\\Support\\MexicoCityTime') ? \App\Support\MexicoCityTime::dateTime($notification->created_at) : $notification->created_at?->format('d/m/Y H:i') }}
                        </span>
                    </div>
                    <p class="mt-3 font-semibold text-[var(--text-main)]">{{ $notification->title }}</p>
                    @if($notification->message)
                        <p class="text-sm text-soft mt-1">{{ $notification->message }}</p>
                    @endif
                    <form method="POST" action="{{ route('notifications.read', $notification) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="text-sm font-semibold text-[var(--primary)] hover:underline">Ver detalle</button>
                    </form>
                </div>
            @endforeach
        </div>
    </section>
@endif

@once
    <script>
        (() => {
            const panel = document.querySelector('[data-notification-panel]');

            if (!panel) {
                return;
            }

            let currentSignature = panel.dataset.notificationSignature || '';
            let inFlight = false;

            async function refreshNotificationsIfNeeded() {
                if (document.hidden || inFlight) {
                    return;
                }

                inFlight = true;

                try {
                    const response = await fetch('{{ route('notifications.pulse') }}', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();
                    const nextSignature = data.signature || '';

                    if (nextSignature !== currentSignature) {
                        window.location.reload();
                    }
                } catch (error) {
                    // La actualización automática no debe interrumpir el uso del sistema.
                } finally {
                    inFlight = false;
                }
            }

            window.addEventListener('focus', refreshNotificationsIfNeeded);
            setInterval(refreshNotificationsIfNeeded, 30000);
        })();
    </script>
@endonce
