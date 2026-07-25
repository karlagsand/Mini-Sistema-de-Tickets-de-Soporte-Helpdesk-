<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAttachment extends Model
{
    protected $fillable = [
        'ticket_id',
        'uploaded_by',
        'original_name',
        'stored_name',
        'path',
        'mime_type',
        'size',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'size' => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleted(function (TicketAttachment $attachment) {
            if ($attachment->path) {
                Storage::disk('local')->delete($attachment->path);
            }
        });
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }


    public function isImage(): bool
    {
        return str_starts_with(strtolower((string) $this->mime_type), 'image/');
    }

    public function isPdf(): bool
    {
        return strtolower((string) $this->mime_type) === 'application/pdf';
    }

    public function isText(): bool
    {
        return str_starts_with(strtolower((string) $this->mime_type), 'text/')
            || in_array(strtolower(pathinfo((string) $this->original_name, PATHINFO_EXTENSION)), ['txt', 'csv', 'log'], true);
    }

    public function canPreview(): bool
    {
        return $this->isImage() || $this->isPdf() || $this->isText();
    }

    public function fileTypeLabel(): string
    {
        if ($this->isImage()) {
            return 'Imagen';
        }

        if ($this->isPdf()) {
            return 'PDF';
        }

        if ($this->isText()) {
            return 'Texto';
        }

        $extension = strtoupper(pathinfo((string) $this->original_name, PATHINFO_EXTENSION));

        return $extension ? 'Archivo ' . $extension : 'Archivo';
    }

    public function fileIcon(): string
    {
        if ($this->isImage()) {
            return 'IMG';
        }

        if ($this->isPdf()) {
            return 'PDF';
        }

        if ($this->isText()) {
            return 'TXT';
        }

        $extension = strtoupper(pathinfo((string) $this->original_name, PATHINFO_EXTENSION));

        return $extension ?: 'DOC';
    }

    public function formattedSize(): string
    {
        $bytes = (int) $this->size;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }
}
