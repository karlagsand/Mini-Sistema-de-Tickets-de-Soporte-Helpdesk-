<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\InternalNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    public function store(Request $request, Ticket $ticket, AuditLogger $auditLogger, InternalNotificationService $notificationService): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $this->authorizeTicketAccess($ticket, $user);

        if ($user->isUserRole()) {
            return back()->with('error', 'Los archivos de soporte solo pueden adjuntarse al momento de registrar la solicitud. Por seguridad del seguimiento, no es posible agregar evidencia después de enviarla.');
        }

        if (($user->isAgent() || $user->isAdmin()) && $ticket->isFinalized() && !$user->isAdmin()) {
            return back()->with('error', 'No es posible agregar archivos a una solicitud finalizada.');
        }

        $validated = $request->validate([
            'attachments' => ['required', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt'],
            'is_internal' => ['nullable', 'boolean'],
        ]);

        // Después de la creación de la solicitud, los archivos agregados por mesa de ayuda
        // se consideran documentación operativa interna para no alterar la evidencia original
        // proporcionada por el solicitante.
        $isInternal = true;
        $uploaded = [];

        foreach ($request->file('attachments', []) as $file) {
            $storedName = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('ticket_attachments/' . $ticket->id, $storedName, 'local');

            $attachment = TicketAttachment::create([
                'ticket_id' => $ticket->id,
                'uploaded_by' => $user->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $storedName,
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize() ?: 0,
                'is_internal' => $isInternal,
            ]);

            $uploaded[] = $attachment->original_name;
        }

        $auditLogger->log(
            'attachment.uploaded',
            'Se agregaron archivos internos al ticket.',
            $ticket,
            null,
            ['files' => $uploaded, 'is_internal' => $isInternal]
        );

        return back()->with('success', 'Archivo(s) interno(s) agregados correctamente.');
    }

    public function preview(TicketAttachment $attachment)
    {
        /** @var User $user */
        $user = Auth::user();
        $attachment->load('ticket');
        $this->authorizeTicketAccess($attachment->ticket, $user);

        if ($attachment->is_internal && $user->isUserRole()) {
            abort(403, 'No autorizado para consultar este archivo.');
        }

        if (!$attachment->canPreview()) {
            abort(415, 'Este tipo de archivo no cuenta con vista previa.');
        }

        if (!Storage::disk('local')->exists($attachment->path)) {
            abort(404, 'Archivo no encontrado.');
        }

        $filePath = Storage::disk('local')->path($attachment->path);
        $fileName = str_replace(['\"', "\r", "\n"], '', $attachment->original_name);
        $mimeType = $attachment->mime_type ?: Storage::disk('local')->mimeType($attachment->path) ?: 'application/octet-stream';

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . addslashes($fileName) . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function download(TicketAttachment $attachment): StreamedResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $attachment->load('ticket');
        $this->authorizeTicketAccess($attachment->ticket, $user);

        if ($attachment->is_internal && $user->isUserRole()) {
            abort(403, 'No autorizado para descargar este archivo.');
        }

        if (!Storage::disk('local')->exists($attachment->path)) {
            abort(404, 'Archivo no encontrado.');
        }

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    public function destroy(TicketAttachment $attachment, AuditLogger $auditLogger): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $attachment->load('ticket');
        $ticket = $attachment->ticket;

        $this->authorizeTicketAccess($ticket, $user);

        if ($user->isUserRole()) {
            return back()->with('error', 'Los archivos enviados con la solicitud no pueden eliminarse porque forman parte de la evidencia del caso.');
        }

        if (!$attachment->is_internal) {
            return back()->with('error', 'Los archivos de soporte iniciales no pueden eliminarse porque forman parte de la evidencia original del caso.');
        }

        if (!$user->isAdmin() && $attachment->uploaded_by !== $user->id) {
            abort(403, 'Solo quien subió el archivo o un administrador puede eliminarlo.');
        }

        $fileName = $attachment->original_name;
        Storage::disk('local')->delete($attachment->path);
        $attachment->delete();

        $auditLogger->log('attachment.deleted', 'Se eliminó un archivo del ticket.', $ticket, ['file' => $fileName]);

        return back()->with('success', 'Archivo eliminado correctamente.');
    }

    private function authorizeTicketAccess(Ticket $ticket, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ($user->isAgent() && ($ticket->assigned_to === $user->id || is_null($ticket->assigned_to))) {
            return;
        }

        if ($user->isUserRole() && $ticket->created_by === $user->id) {
            return;
        }

        abort(403, 'No autorizado para acceder a esta solicitud.');
    }
}
