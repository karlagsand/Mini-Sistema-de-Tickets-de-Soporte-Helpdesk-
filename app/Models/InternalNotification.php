<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalNotification extends Model
{
    protected $fillable = [
        'user_id',
        'ticket_id',
        'type',
        'title',
        'message',
        'url',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }
}
