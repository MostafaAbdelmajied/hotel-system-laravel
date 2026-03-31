<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminClientController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $state = (string) $request->input('state', 'all');
        $searchField = (string) $request->input('search_field', 'all');

        if (! in_array($state, ['all', 'pending', 'approved', 'banned'], true)) {
            $state = 'all';
        }

        if (! in_array($searchField, ['all', 'name', 'email', 'mobile_number', 'country'], true)) {
            $searchField = 'all';
        }

        $clientsQuery = User::query()
            ->role('Client')
            ->with('approvedBy:id,name');

        if ($search !== '') {
            $clientsQuery->where(function (Builder $query) use ($search, $searchField): void {
                if ($searchField === 'name') {
                    $query->where('name', 'like', "%{$search}%");

                    return;
                }

                if ($searchField === 'email') {
                    $query->where('email', 'like', "%{$search}%");

                    return;
                }

                if ($searchField === 'mobile_number') {
                    $query->where('mobile_number', 'like', "%{$search}%");

                    return;
                }

                if ($searchField === 'country') {
                    $query->where('country', 'like', "%{$search}%");

                    return;
                }

                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%");
            });
        }

        if ($state === 'pending') {
            $clientsQuery->where('status', UserStatus::Pending->value);
        }

        if ($state === 'approved') {
            $clientsQuery
                ->where('status', UserStatus::Approved->value)
                ->whereNull('banned_at');
        }

        if ($state === 'banned') {
            $clientsQuery->whereNotNull('banned_at');
        }

        $clients = $clientsQuery
            ->select([
                'id',
                'name',
                'email',
                'mobile_number',
                'country',
                'gender',
                'status',
                'approved_by',
                'approved_at',
                'banned_at',
                'created_at',
            ])
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(function (User $client): array {
                $bannedAt = $client->getAttribute('banned_at');
                $status = $client->status?->value ?? UserStatus::Pending->value;

                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                    'mobile_number' => $client->mobile_number,
                    'country' => $client->country,
                    'gender' => $client->gender?->value,
                    'status' => $status,
                    'state' => $bannedAt !== null ? 'banned' : $status,
                    'approved_at' => $client->approved_at,
                    'approved_by_name' => $client->approvedBy?->name,
                    'banned_at' => $bannedAt,
                    'created_at' => $client->created_at,
                ];
            });

        $clientsBaseQuery = User::query()->role('Client');

        return Inertia::render('Admin/Clients/Index', [
            'clients' => $clients,
            'filters' => [
                'search' => $search,
                'state' => $state,
                'search_field' => $searchField,
            ],
            'stats' => [
                'total' => (clone $clientsBaseQuery)->count(),
                'pending' => (clone $clientsBaseQuery)->where('status', UserStatus::Pending->value)->count(),
                'approved' => (clone $clientsBaseQuery)
                    ->where('status', UserStatus::Approved->value)
                    ->whereNull('banned_at')
                    ->count(),
                'banned' => (clone $clientsBaseQuery)->whereNotNull('banned_at')->count(),
            ],
        ]);
    }
}
