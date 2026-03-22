<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketCommentController extends Controller
{
    public function store(Request $request, Ticket $ticket): RedirectResponse
    {
        $request->validate([
            'comment' => ['required', 'string'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'comment' => $request->comment,
            'is_internal' => $user->isAgent() || $user->isAdmin(),
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Comentario agregado correctamente.');
    }
}