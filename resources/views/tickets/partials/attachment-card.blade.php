@php
    $canPreview = method_exists($attachment, 'canPreview') && $attachment->canPreview();
    $isImage = method_exists($attachment, 'isImage') && $attachment->isImage();
    $typeLabel = method_exists($attachment, 'fileTypeLabel') ? $attachment->fileTypeLabel() : 'Archivo';
    $icon = method_exists($attachment, 'fileIcon') ? $attachment->fileIcon() : '📎';
@endphp

<div class="attachment-card {{ !empty($isInternalSection) ? 'attachment-card-internal' : '' }}">
    <div class="attachment-preview-box">
        @if($isImage)
            <a href="{{ route('tickets.attachments.preview', $attachment) }}" target="_blank" rel="noopener" aria-label="Abrir vista previa de {{ $attachment->original_name }}">
                <img src="{{ route('tickets.attachments.preview', $attachment) }}" alt="Vista previa de {{ $attachment->original_name }}">
            </a>
        @elseif($canPreview)
            <a href="{{ route('tickets.attachments.preview', $attachment) }}" target="_blank" rel="noopener" class="attachment-preview-icon" aria-label="Abrir vista previa de {{ $attachment->original_name }}">
                <span>{{ $icon }}</span>
                <small>Vista previa</small>
            </a>
        @else
            <div class="attachment-preview-icon muted">
                <span>{{ $icon }}</span>
                <small>Descarga</small>
            </div>
        @endif
    </div>

    <div class="attachment-meta">
        <div class="attachment-title-row">
            <p class="attachment-title">{{ $attachment->original_name }}</p>
            @if(!empty($isInternalSection))
                <span class="app-badge badge-slate">Interno</span>
            @endif
        </div>
        <p class="attachment-details">{{ $typeLabel }} · {{ $attachment->formattedSize() }} · {{ $attachment->uploader->name ?? 'Usuario' }}</p>
        <p class="attachment-details">{{ \App\Support\MexicoCityTime::dateTime($attachment->created_at) }}</p>
        <div class="attachment-actions">
            @if($canPreview)
                <a href="{{ route('tickets.attachments.preview', $attachment) }}" target="_blank" rel="noopener" class="app-btn-secondary text-sm">Vista previa</a>
            @endif
            <a href="{{ route('tickets.attachments.download', $attachment) }}" class="app-btn-secondary text-sm">Descargar</a>
            @if(!empty($canDelete))
                <form method="POST" action="{{ route('tickets.attachments.destroy', $attachment) }}" onsubmit="return confirm('¿Eliminar este archivo?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="app-btn-danger text-sm">Eliminar</button>
                </form>
            @endif
        </div>
    </div>
</div>
