<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyApprovedClientController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewMyApprovedClients', User::class);

        $approvedClients = User::query()
            ->select([
                'id',
                'name',
                'email',
                'country',
                'gender',
                'approved_at',
            ])
            ->where('status', UserStatus::Approved->value)
            ->when(
                ! $request->user()->hasRole('Admin'),
                fn ($query) => $query->where('approved_by', $request->user()->id)
            )
            ->whereHas('roles', function ($query): void {
                $query->whereIn('name', ['Client', 'client']);
            })
            ->orderByDesc('approved_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('clients/my-approved-clients', [
            'approvedClients' => $approvedClients,
        ]);
    }
}
