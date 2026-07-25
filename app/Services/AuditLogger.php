<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    public function log(string $action, string $description, ?Ticket $ticket = null, ?array $oldValues = null, ?array $newValues = null, User|int|null $user = null): AuditLog
    {
        $actorId = $user instanceof User ? $user->id : ($user ?: Auth::id());

        return AuditLog::create([
            'user_id' => $actorId,
            'ticket_id' => $ticket?->id,
            'action' => $action,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 500),
        ]);
    }
}
