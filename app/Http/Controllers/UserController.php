<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with('role')
            ->orderByDesc('attention_weight')
            ->latest()
            ->paginate(10);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $roles = Role::orderBy('name')->get();
        $attentionLevels = User::attentionLevels();

        return view('users.create', compact('roles', 'attentionLevels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'exists:roles,id'],
            'position_level' => ['required', Rule::in(array_keys(User::attentionLevels()))],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
            'position_level' => $validated['position_level'],
            'attention_weight' => User::attentionWeightFor($validated['position_level']),
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function show(User $user): View
    {
        $user->load('role');

        return view('users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $roles = Role::orderBy('name')->get();
        $attentionLevels = User::attentionLevels();

        return view('users.edit', compact('user', 'roles', 'attentionLevels'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'exists:roles,id'],
            'position_level' => ['required', Rule::in(array_keys(User::attentionLevels()))],
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
            'position_level' => $validated['position_level'],
            'attention_weight' => User::attentionWeightFor($validated['position_level']),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if (Auth::id() === $user->id) {
            return redirect()
                ->route('users.index')
                ->with('error', 'No puedes eliminar tu propio usuario.');
        }

        if ($user->createdTickets()->exists() || $user->assignedTickets()->exists() || $user->comments()->exists()) {
            return redirect()
                ->route('users.index')
                ->with('error', 'No se puede eliminar el usuario porque tiene relación con tickets o comentarios registrados.');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }
}
