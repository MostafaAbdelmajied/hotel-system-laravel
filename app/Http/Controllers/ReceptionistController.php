<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReceptionistController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasRole('Admin'), 403);

        $search = trim((string) $request->input('search', ''));

        $receptionists = User::query()
            ->role('Receptionist')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->select([
                'id',
                'name',
                'email',
                'country',
                'gender',
                'avatar',
                'created_at',
            ])
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $receptionist): array => [
                'id' => $receptionist->id,
                'name' => $receptionist->name,
                'email' => $receptionist->email,
                'country' => $receptionist->country,
                'gender' => $receptionist->gender?->value,
                'avatar' => $receptionist->avatar,
                'created_at' => $receptionist->created_at,
            ]);

        return Inertia::render('Admin/Receptionists/Index', [
            'receptionists' => $receptionists,
            'countries' => cachedCountries(),
            'filters' => ['search' => $search],
        ]);
    }
}
