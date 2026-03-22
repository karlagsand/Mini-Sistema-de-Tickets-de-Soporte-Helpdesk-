<?php

namespace App\Http\Controllers;

use App\Models\Priority;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PriorityController extends Controller
{
    public function index(): View
    {
        $priorities = Priority::orderBy('level')->paginate(10);

        return view('priorities.index', compact('priorities'));
    }

    public function create(): View
    {
        return view('priorities.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:priorities,name'],
            'level' => ['required', 'integer', 'min:1', 'max:10', 'unique:priorities,level'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        Priority::create([
            'name' => $request->name,
            'level' => $request->level,
            'color' => $request->color,
        ]);

        return redirect()
            ->route('priorities.index')
            ->with('success', 'Prioridad creada correctamente.');
    }

    public function show(Priority $priority): View
    {
        return view('priorities.show', compact('priority'));
    }

    public function edit(Priority $priority): View
    {
        return view('priorities.edit', compact('priority'));
    }

    public function update(Request $request, Priority $priority): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:priorities,name,' . $priority->id],
            'level' => ['required', 'integer', 'min:1', 'max:10', 'unique:priorities,level,' . $priority->id],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $priority->update([
            'name' => $request->name,
            'level' => $request->level,
            'color' => $request->color,
        ]);

        return redirect()
            ->route('priorities.index')
            ->with('success', 'Prioridad actualizada correctamente.');
    }

    public function destroy(Priority $priority): RedirectResponse
    {
        if ($priority->tickets()->exists()) {
            return redirect()
                ->route('priorities.index')
                ->with('error', 'No se puede eliminar la prioridad porque está siendo utilizada en uno o más tickets.');
        }

        $priority->delete();

        return redirect()
            ->route('priorities.index')
            ->with('success', 'Prioridad eliminada correctamente.');
    }
}