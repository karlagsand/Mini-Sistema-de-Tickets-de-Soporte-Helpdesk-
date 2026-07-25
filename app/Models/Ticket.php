<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    public const REQUEST_TYPES = [
        'incidente' => 'Algo no funciona',
        'solicitud' => 'Necesito apoyo o servicio',
        'consulta' => 'Tengo una duda',
        'cambio' => 'Requiero un cambio o actualización',
    ];

    public const REPORTED_IMPACT_OPTIONS = [
        'sin_trabajar' => 'No puedo continuar con mi trabajo',
        'varias_personas' => 'Afecta a varias personas',
        'solo_mi_equipo' => 'Afecta solo a mi equipo o usuario',
        'duda_general' => 'Es una duda o solicitud general',
    ];

    public const IMPACT_LEVELS = [
        'alto' => 'Alto',
        'medio' => 'Medio',
        'bajo' => 'Bajo',
    ];

    public const URGENCY_LEVELS = [
        'alta' => 'Alta',
        'media' => 'Media',
        'baja' => 'Baja',
    ];

    protected $fillable = [
        'folio',
        'subject',
        'description',
        'request_type',
        'reported_impact',
        'category_id',
        'priority_id',
        'impact',
        'urgency',
        'priority_reviewed_at',
        'status_id',
        'created_by',
        'assigned_to',
        'opened_at',
        'first_responded_at',
        'first_response_due_at',
        'resolution_due_at',
        'resolved_at',
        'closed_at',
        'satisfaction_rating',
        'satisfaction_comment',
        'satisfaction_submitted_at',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'first_responded_at' => 'datetime',
        'first_response_due_at' => 'datetime',
        'resolution_due_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'priority_reviewed_at' => 'datetime',
        'satisfaction_submitted_at' => 'datetime',
        'satisfaction_rating' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'status_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TicketStatusHistory::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(InternalNotification::class);
    }

    public function requestTypeLabel(): string
    {
        return self::REQUEST_TYPES[$this->request_type ?? 'incidente'] ?? 'Solicitud';
    }

    public function reportedImpactLabel(): string
    {
        return self::REPORTED_IMPACT_OPTIONS[$this->reported_impact ?? ''] ?? 'No especificado';
    }

    public function impactLabel(): string
    {
        return self::IMPACT_LEVELS[$this->impact ?? ''] ?? 'Sin clasificar';
    }

    public function urgencyLabel(): string
    {
        return self::URGENCY_LEVELS[$this->urgency ?? ''] ?? 'Sin clasificar';
    }

    public function priorityLabel(): string
    {
        return $this->priority?->name ?? 'Sin clasificar';
    }

    public function statusSlug(): string
    {
        return strtolower((string) ($this->status?->slug ?? ''));
    }

    public function isClosed(): bool
    {
        return (bool) ($this->status?->is_closed ?? false);
    }

    public function isFinalized(): bool
    {
        return in_array($this->statusSlug(), ['cerrado', 'cancelado'], true);
    }

    public function hasSolutionRegistered(): bool
    {
        return $this->statusSlug() === 'resuelto';
    }

    public function isResolved(): bool
    {
        return $this->hasSolutionRegistered();
    }

    public function canRequesterAddFollowUp(): bool
    {
        return !in_array($this->statusSlug(), ['resuelto', 'cerrado', 'cancelado'], true);
    }

    public function canRequesterRequestReview(): bool
    {
        return $this->hasSolutionRegistered();
    }

    public function canRequesterCloseWithSatisfaction(): bool
    {
        return $this->hasSolutionRegistered();
    }

    public function hasSla(): bool
    {
        return !is_null($this->first_response_due_at) || !is_null($this->resolution_due_at);
    }

    public function firstResponseSlaLabel(): string
    {
        if (!$this->first_response_due_at) {
            return 'Sin tiempo calculado';
        }

        if ($this->first_responded_at) {
            return $this->first_responded_at->lessThanOrEqualTo($this->first_response_due_at)
                ? 'Respondido en tiempo'
                : 'Respuesta fuera de tiempo';
        }

        return now()->lessThanOrEqualTo($this->first_response_due_at)
            ? 'Respuesta en tiempo'
            : 'Respuesta vencida';
    }

    public function resolutionSlaLabel(): string
    {
        if (!$this->resolution_due_at) {
            return 'Sin tiempo calculado';
        }

        $finishedAt = $this->closed_at ?? $this->resolved_at;

        if ($finishedAt) {
            return $finishedAt->lessThanOrEqualTo($this->resolution_due_at)
                ? 'Solución en tiempo'
                : 'Solución fuera de tiempo';
        }

        return now()->lessThanOrEqualTo($this->resolution_due_at)
            ? 'Solución en tiempo'
            : 'Solución vencida';
    }
}
