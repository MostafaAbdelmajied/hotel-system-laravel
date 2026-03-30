<?php

namespace App\Http\Responses;

use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        if ($request->header('X-Inertia')) {
            return Inertia::location(Fortify::redirects('login'));
        }

        return redirect()->intended(Fortify::redirects('login'));
    }
}
