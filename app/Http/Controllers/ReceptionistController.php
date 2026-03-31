<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Http\Requests\StoreReceptionistRequest;
use App\Http\Requests\UpdateReceptionistRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ReceptionistController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string)$request->input('search', ''));

        $receptionists = User::query()
            ->role('Receptionist')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%");
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
                'banned_at',
            ])
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn(User $receptionist): array => [
                'id' => $receptionist->id,
                'name' => $receptionist->name,
                'email' => $receptionist->email,
                'country' => $receptionist->country,
                'gender' => $receptionist->gender?->value,
                'avatar' => $receptionist->avatar,
                'created_at' => $receptionist->created_at,
                'is_banned' => $receptionist->isBanned(),
            ]);

        return Inertia::render('Admin/Receptionists/Index', [
            'receptionists' => $receptionists,
            'filters' => ['search' => $search],
        ]);
    }

    public function edit(User $receptionist): Response
    {
        abort_unless($receptionist->hasRole('Receptionist'), 404);

        return Inertia::render('Admin/Receptionists/Edit', [
            'receptionist' => [
                'id' => $receptionist->id,
                'name' => $receptionist->name,
                'email' => $receptionist->email,
                'country' => $receptionist->country,
                'gender' => $receptionist->gender?->value,
                'avatar' => $receptionist->avatar,
            ],
            'countries' => cachedCountries(),
        ]);
    }

    public function update(UpdateReceptionistRequest $request, User $receptionist): RedirectResponse
    {
        $validated = $request->validated();

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'country' => $validated['country'],
            'gender' => $validated['gender'],
            'avatar' => $this->replaceAvatar($receptionist, $request->file('avatar')) ?? $receptionist->avatar,
        ];

        if (filled($validated['password'] ?? null)) {
            $payload['password'] = $validated['password'];
        }

        $receptionist->update($payload);

        return back()->with('success', 'Receptionist updated successfully.');
    }

    private function replaceAvatar(User $receptionist, ?UploadedFile $avatar): ?string
    {
        if ($avatar === null) {
            return null;
        }

        if ($receptionist->avatar !== null && $receptionist->avatar !== 'default.png') {
            Storage::disk('public')->delete($receptionist->avatar);
        }

        return $avatar->store('avatars', 'public');
    }

    public function store(StoreReceptionistRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();

        DB::transaction(function () use ($user, $validated, $request): void {
            $receptionist = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'country' => $validated['country'],
                'gender' => $validated['gender'],
                'avatar' => $this->storeAvatar($request->file('avatar')),
                'status' => UserStatus::Approved,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $receptionist->assignRole('Receptionist');
        });

        return back()->with('success', 'Receptionist created successfully.');
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Receptionists/Create', [
            'countries' => cachedCountries(),
        ]);
    }

    private function storeAvatar(?UploadedFile $avatar): ?string
    {
        return $avatar?->store('avatars', 'public');
    }

    public function destroy(User $receptionist): RedirectResponse
    {
        abort_unless($receptionist->hasRole('Receptionist'), 404);

        if ($receptionist->avatar !== null && $receptionist->avatar !== 'default.png') {
            Storage::disk('public')->delete($receptionist->avatar);
        }

        $receptionist->delete();

        return back()->with('success', 'Receptionist deleted successfully.');
    }

    public function toggleStatus(User $receptionist): RedirectResponse
    {
        $receptionist->banned_at = $receptionist->isBanned() ? null : now();
        $receptionist->save();

        $msg = $receptionist->isBanned() ? 'Receptionist banned.' : 'Receptionist unbanned.';

        return back()->with('success', $msg);
    }
}
