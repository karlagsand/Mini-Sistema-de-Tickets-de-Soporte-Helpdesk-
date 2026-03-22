<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
            $stats = [
                'total' => Ticket::count(),
                'new' => Ticket::whereHas('status', fn ($q) => $q->where('slug', 'nuevo'))->count(),
                'in_progress' => Ticket::whereHas('status', fn ($q) => $q->where('slug', 'en-proceso'))->count(),
                'closed' => Ticket::whereHas('status', fn ($q) => $q->where('slug', 'cerrado'))->count(),
            ];
        } elseif ($user->isAgent()) {
            $stats = [
                'total' => Ticket::where('assigned_to', $user->id)->count(),
                'new' => Ticket::where('assigned_to', $user->id)
                    ->whereHas('status', fn ($q) => $q->where('slug', 'nuevo'))
                    ->count(),
                'in_progress' => Ticket::where('assigned_to', $user->id)
                    ->whereHas('status', fn ($q) => $q->where('slug', 'en-proceso'))
                    ->count(),
                'closed' => Ticket::where('assigned_to', $user->id)
                    ->whereHas('status', fn ($q) => $q->where('slug', 'cerrado'))
                    ->count(),
            ];
        } else {
            $stats = [
                'total' => Ticket::where('created_by', $user->id)->count(),
                'new' => Ticket::where('created_by', $user->id)
                    ->whereHas('status', fn ($q) => $q->where('slug', 'nuevo'))
                    ->count(),
                'in_progress' => Ticket::where('created_by', $user->id)
                    ->whereHas('status', fn ($q) => $q->where('slug', 'en-proceso'))
                    ->count(),
                'closed' => Ticket::where('created_by', $user->id)
                    ->whereHas('status', fn ($q) => $q->where('slug', 'cerrado'))
                    ->count(),
            ];
        }

        return view('dashboard', compact('stats', 'user'));
    }
}