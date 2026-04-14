<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permite administradores u operadores (configuración operativa sin rol de solo lectura).
 */
class EnsureRoleOps
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
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

        $role = (string) ($user->role ?? '');
        if (! in_array($role, ['admin', 'operator'], true)) {
            if ($request->expectsJson()) {
                abort(403, 'No tienes permisos para acceder a esta sección.');
            }

            return redirect()->route('profile.edit')->with('error', 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}
