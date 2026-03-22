<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        $query = Ticket::with([
            'category',
            'priority',
            'status',
            'creator',
            'assignee',
        ]);

        if ($user->isUserRole()) {
            $query->where('created_by', $user->id);
        }

        if ($user->isAgent()) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                    ->orWhereNull('assigned_to');
            });
        }

        if ($request->filled('status')) {
            $query->where('status_id', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority_id', $request->priority);
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $tickets = $query->latest()->paginate(10)->withQueryString();

        $statuses = TicketStatus::orderBy('sort_order')->get();
        $priorities = Priority::orderBy('level')->get();
        $categories = Category::orderBy('name')->get();

        return view('tickets.index', compact('tickets', 'statuses', 'priorities', 'categories'));
    }

    public function create(): View
    {
        return view('tickets.create', [
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'priorities' => Priority::orderBy('level')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'priority_id' => ['required', 'exists:priorities,id'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $newStatus = TicketStatus::where('slug', 'nuevo')->firstOrFail();

        $ticket = Ticket::create([
            'folio' => $this->generateFolio(),
            'subject' => $request->subject,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'priority_id' => $request->priority_id,
            'status_id' => $newStatus->id,
            'created_by' => $user->id,
            'opened_at' => now(),
        ]);

        TicketStatusHistory::create([
            'ticket_id' => $ticket->id,
            'previous_status_id' => null,
            'new_status_id' => $newStatus->id,
            'changed_by' => $user->id,
            'changed_at' => now(),
            'notes' => 'Ticket creado',
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Ticket creado correctamente.');
    }

    public function show(Ticket $ticket): View
    {
        $this->authorizeTicketAccess($ticket);

        $ticket->load([
            'category',
            'priority',
            'status',
            'creator.role',
            'assignee.role',
            'comments.user',
            'histories.previousStatus',
            'histories.newStatus',
            'histories.changedBy',
        ]);

        $agents = User::whereHas('role', function ($q) {
            $q->where('name', 'Agente');
        })->orderBy('name')->get();

        $statuses = TicketStatus::orderBy('sort_order')->get();

        return view('tickets.show', compact('ticket', 'agents', 'statuses'));
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->isUserRole()) {
            abort(403, 'No autorizado para actualizar tickets.');
        }

        if ($user->isAgent() && !is_null($ticket->assigned_to) && $ticket->assigned_to !== $user->id) {
            abort(403, 'No autorizado para actualizar este ticket.');
        }

        $request->validate([
            'status_id' => ['nullable', 'exists:ticket_statuses,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        if ($user->isAgent() && $request->filled('assigned_to') && (int) $request->assigned_to !== $user->id) {
            return redirect()
                ->route('tickets.show', $ticket)
                ->with('error', 'Un agente solo puede autoasignarse tickets o trabajar con tickets ya asignados a su usuario.');
        }

        $previousStatusId = $ticket->status_id;
        $notes = [];
        $statusChanged = false;

        if ($request->has('assigned_to')) {
            $newAssignedTo = $request->filled('assigned_to') ? (int) $request->assigned_to : null;

            if ($ticket->assigned_to !== $newAssignedTo) {
                $ticket->assigned_to = $newAssignedTo;
                $notes[] = 'Ticket asignado o reasignado';
            }
        }

        if ($request->filled('status_id') && (int) $request->status_id !== (int) $ticket->status_id) {
            $ticket->status_id = (int) $request->status_id;
            $notes[] = 'Cambio de estado';
            $statusChanged = true;

            $newStatus = TicketStatus::find($request->status_id);

            if ($newStatus?->slug === 'resuelto' && is_null($ticket->resolved_at)) {
                $ticket->resolved_at = now();
            }

            if ($newStatus?->slug === 'cerrado' && is_null($ticket->closed_at)) {
                $ticket->closed_at = now();
            }
        }

        $ticket->save();

        if ($statusChanged) {
            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'previous_status_id' => $previousStatusId,
                'new_status_id' => $ticket->status_id,
                'changed_by' => $user->id,
                'changed_at' => now(),
                'notes' => !empty($notes) ? implode(' | ', $notes) : 'Actualización de ticket',
            ]);
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Ticket actualizado correctamente.');
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->isAdmin()) {
            abort(403, 'No autorizado para eliminar tickets.');
        }

        $ticket->delete();

        return redirect()
            ->route('tickets.index')
            ->with('success', 'Ticket eliminado correctamente.');
    }

    private function generateFolio(): string
    {
        return 'HD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }

    private function authorizeTicketAccess(Ticket $ticket): void
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isAgent()) {
            if ($ticket->assigned_to === $user->id || is_null($ticket->assigned_to)) {
                return;
            }
        }

        if ($user->isUserRole() && $ticket->created_by === $user->id) {
            return;
        }

        abort(403, 'No autorizado para ver este ticket.');
    }
}