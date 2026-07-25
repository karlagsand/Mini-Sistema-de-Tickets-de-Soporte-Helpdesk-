<?php

namespace App\Services;

use App\Models\InternalNotification;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;

class InternalNotificationService
{
    public function createForUser(User|int|null $user, ?Ticket $ticket, string $title, string $message, string $type = 'info', ?string $url = null): ?InternalNotification
    {
        $userId = $user instanceof User ? $user->id : $user;

        if (!$userId) {
            return null;
        }

        return InternalNotification::create([
            'user_id' => $userId,
            'ticket_id' => $ticket?->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'url' => $url ?? ($ticket ? route('tickets.show', $ticket) : route('dashboard')),
        ]);
    }

    public function notifyAdmins(?Ticket $ticket, string $title, string $message, string $type = 'info', ?string $url = null): void
    {
        User::whereHas('role', fn ($q) => $q->where('name', 'Administrador'))
            ->get()
            ->each(fn (User $admin) => $this->createForUser($admin, $ticket, $title, $message, $type, $url));
    }

    public function notifyTicketParticipants(Ticket $ticket, string $title, string $message, string $type = 'info', ?int $excludeUserId = null): void
    {
        $userIds = collect([$ticket->created_by, $ticket->assigned_to])
            ->filter()
            ->unique()
            ->reject(fn ($id) => (int) $id === (int) $excludeUserId);

        $userIds->each(fn ($id) => $this->createForUser((int) $id, $ticket, $title, $message, $type));
    }

    public function unreadForUser(User $user, int $limit = 8): Collection
    {
        return InternalNotification::with('ticket')
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function recentForUser(User $user, int $limit = 8): Collection
    {
        return $this->unreadForUser($user, $limit);
    }

    public function dashboardSignature(User $user, int $limit = 8): string
    {
        $notifications = $this->unreadForUser($user, $limit);

        return $notifications->pluck('id')->implode('-') . ':' . $this->unreadCount($user);
    }

    public function unreadCount(User $user): int
    {
        return InternalNotification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }
}
