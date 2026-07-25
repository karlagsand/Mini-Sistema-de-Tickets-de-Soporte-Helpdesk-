<?php

namespace App\Http\Controllers;

use App\Models\InternalNotification;
use App\Models\User;
use App\Services\InternalNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function markAsRead(InternalNotification $notification): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($notification->user_id !== $user->id) {
            abort(403, 'No autorizado para actualizar esta notificación.');
        }

        if (is_null($notification->read_at)) {
            $notification->update(['read_at' => now()]);
        }

        return redirect($notification->url ?: route('dashboard'));
    }

    public function markAllAsRead(): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        InternalNotification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'Notificaciones marcadas como leídas.');
    }

    public function pulse(InternalNotificationService $notifications): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        return response()->json([
            'unread_count' => $notifications->unreadCount($user),
            'signature' => $notifications->dashboardSignature($user),
        ]);
    }
}
