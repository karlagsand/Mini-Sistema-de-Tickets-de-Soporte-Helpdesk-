<?php

namespace App\Http\Controllers;

use App\Models\TicketStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketStatusController extends Controller
{
    public function index(): View
    {
        $statuses = TicketStatus::orderBy('sort_order')->paginate(10);

        return view('ticket-statuses.index', compact('statuses'));
    }

    public function create(): View
    {
        return view('ticket-statuses.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:ticket_statuses,name'],
            'slug' => ['required', 'string', 'max:50', 'unique:ticket_statuses,slug'],
            'sort_order' => ['required', 'integer', 'min:1', 'max:100'],
            'is_closed' => ['nullable', 'boolean'],
        ]);

        TicketStatus::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'sort_order' => $request->sort_order,
            'is_closed' => $request->boolean('is_closed'),
        ]);

        return redirect()
            ->route('ticket-statuses.index')
            ->with('success', 'Estado creado correctamente.');
    }

    public function show(TicketStatus $ticket_status): View
    {
        return view('ticket-statuses.show', ['status' => $ticket_status]);
    }

    public function edit(TicketStatus $ticket_status): View
    {
        return view('ticket-statuses.edit', ['status' => $ticket_status]);
    }

    public function update(Request $request, TicketStatus $ticket_status): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:ticket_statuses,name,' . $ticket_status->id],
            'slug' => ['required', 'string', 'max:50', 'unique:ticket_statuses,slug,' . $ticket_status->id],
            'sort_order' => ['required', 'integer', 'min:1', 'max:100'],
            'is_closed' => ['nullable', 'boolean'],
        ]);

        $ticket_status->update([
            'name' => $request->name,
            'slug' => $request->slug,
            'sort_order' => $request->sort_order,
            'is_closed' => $request->boolean('is_closed'),
        ]);

        return redirect()
            ->route('ticket-statuses.index')
            ->with('success', 'Estado actualizado correctamente.');
    }

    public function destroy(TicketStatus $ticket_status): RedirectResponse
    {
        $ticket_status->delete();

        return redirect()
            ->route('ticket-statuses.index')
            ->with('success', 'Estado eliminado correctamente.');
    }
}