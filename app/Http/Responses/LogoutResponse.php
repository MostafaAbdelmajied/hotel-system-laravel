<?php

namespace App\Http\Responses;

use Inertia\Inertia;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): Response
    {
        if ($request->header('X-Inertia')) {
            return Inertia::location(Fortify::redirects('logout', '/'));
        }

        return redirect(Fortify::redirects('logout', '/'));
    }
}
