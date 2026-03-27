<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Http\Requests\StoreManagerRequest;
use App\Http\Requests\UpdateManagerRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ManagerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));

        $managers = User::query()
            ->role('Manager')
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
            ->through(fn (User $manager): array => [
                'id' => $manager->id,
                'name' => $manager->name,
                'email' => $manager->email,
                'country' => $manager->country,
                'gender' => $manager->gender?->value,
                'avatar' => $manager->avatar,
                'created_at' => $manager->created_at,
            ]);

        return Inertia::render('Admin/Managers/Index', [
            'managers' => $managers,
            'countries' => cachedCountries(),
            'filters' => ['search' => $search],
        ]);
    }

    public function store(StoreManagerRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();

        DB::transaction(function () use ($user, $validated, $request): void {
            $manager = User::query()->create([
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

            $manager->assignRole('Manager');
        });

        return back()->with('success', 'Manager created successfully.');
    }

    public function update(UpdateManagerRequest $request, User $manager): RedirectResponse
    {
        $validated = $request->validated();

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'country' => $validated['country'],
            'gender' => $validated['gender'],
            'avatar' => $this->replaceAvatar($manager, $request->file('avatar')) ?? $manager->avatar,
        ];

        if (filled($validated['password'] ?? null)) {
            $payload['password'] = $validated['password'];
        }

        $manager->update($payload);

        return back()->with('success', 'Manager updated successfully.');
    }

    public function destroy(Request $request, User $manager): RedirectResponse
    {
        abort_unless(
            $request->user()?->hasRole('Admin') && $manager->hasRole('Manager'),
            403
        );

        if ($manager->avatar !== null && $manager->avatar !== 'default.png') {
            Storage::disk('public')->delete($manager->avatar);
        }

        $manager->delete();

        return back()->with('success', 'Manager deleted successfully.');
    }

    private function storeAvatar(?UploadedFile $avatar): ?string
    {
        if ($avatar === null) {
            return null;
        }

        return $avatar->store('avatars', 'public');
    }

    private function replaceAvatar(User $manager, ?UploadedFile $avatar): ?string
    {
        if ($avatar === null) {
            return null;
        }

        if ($manager->avatar !== null && $manager->avatar !== 'default.png') {
            Storage::disk('public')->delete($manager->avatar);
        }

        return $avatar->store('avatars', 'public');
    }
}
