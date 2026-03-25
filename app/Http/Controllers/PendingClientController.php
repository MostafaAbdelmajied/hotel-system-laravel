<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PendingClientController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewPendingClients', User::class);

        $pendingClients = User::query()
            ->select([
                'id',
                'name',
                'email',
                'country',
                'gender',
                'created_at',
            ])
            ->where('status', UserStatus::Pending->value)
            ->whereHas('roles', function ($query): void {
                $query->whereIn('name', ['Client', 'client']);
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('clients/pending-clients', [
            'pendingClients' => $pendingClients,
        ]);
    }
}
