<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !$user->role) {
            abort(403, 'Acceso no autorizado.');
        }

        if (!in_array($user->role->name, $roles)) {
            abort(403, 'No cuenta con permisos para acceder a este módulo.');
        }

        return $next($request);
    }
}