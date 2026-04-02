<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if ($permissions === []) {
            return $next($request);
        }

        foreach ($permissions as $permission) {
            if (user_can($permission)) {
                return $next($request);
            }
        }

        abort(403, 'No tienes permisos para esta sección.');
    }
}
