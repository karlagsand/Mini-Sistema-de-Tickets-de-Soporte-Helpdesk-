<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ATTENTION_LEVELS = [
        'director_general' => [
            'label' => 'Director General',
            'weight' => 100,
        ],
        'subdirector' => [
            'label' => 'Subdirector',
            'weight' => 80,
        ],
        'gerente' => [
            'label' => 'Gerente',
            'weight' => 60,
        ],
        'operativo' => [
            'label' => 'Operativo',
            'weight' => 20,
        ],
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'position_level',
        'attention_weight',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'attention_weight' => 'integer',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function createdTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'created_by');
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    public function isAdmin(): bool
    {
        return optional($this->role)->name === 'Administrador';
    }

    public function isAgent(): bool
    {
        return optional($this->role)->name === 'Agente';
    }

    public function isUserRole(): bool
    {
        return optional($this->role)->name === 'Usuario';
    }

    public static function attentionLevels(): array
    {
        return self::ATTENTION_LEVELS;
    }

    public static function attentionWeightFor(?string $level): int
    {
        return self::ATTENTION_LEVELS[$level]['weight'] ?? self::ATTENTION_LEVELS['operativo']['weight'];
    }

    public function attentionLabel(): string
    {
        return self::ATTENTION_LEVELS[$this->position_level ?? 'operativo']['label']
            ?? self::ATTENTION_LEVELS['operativo']['label'];
    }
}
