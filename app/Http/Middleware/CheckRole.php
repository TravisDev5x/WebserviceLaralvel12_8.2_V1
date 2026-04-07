<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        if (! $user->is_active) {
            auth()->logout();
            if ($request->expectsJson()) {
                abort(403, 'Tu usuario está desactivado.');
            }

            return redirect()->route('login')->withErrors(['login' => 'Tu usuario está desactivado.']);
        }

        if ($roles !== [] && ! in_array((string) $user->role, $roles, true)) {
            if ($request->expectsJson()) {
                abort(403, 'No tienes permisos para acceder a esta sección.');
            }

            return redirect('/monitor')->with('error', 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}
