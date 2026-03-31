<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Http\Requests\StoreManagerRequest;
use App\Http\Requests\UpdateManagerRequest;
use App\Models\User;
use BackedEnum;
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

        $managersQuery = User::query()
            ->role('Manager');

        if ($search !== '') {
            $managersQuery->where(function (Builder $query) use ($search): void {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $managers = $managersQuery
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
            ->through(fn (User $manager): array => $this->serializeManager($manager, true));

        return Inertia::render('Admin/Managers/Index', [
            'managers' => $managers,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Managers/Create', [
            'countries' => cachedCountries(),
        ]);
    }

    public function edit(User $manager): Response
    {
        abort_unless($manager->hasRole('Manager'), 404);

        return Inertia::render('Admin/Managers/Edit', [
            'manager' => $this->serializeManager($manager),
            'countries' => cachedCountries(),
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
                'approved_by' => $user->getKey(),
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
            'avatar' => $this->replaceAvatar($manager, $request->file('avatar')) ?? $this->nullableString($manager->getAttribute('avatar')),
        ];

        if (filled($validated['password'] ?? null)) {
            $payload['password'] = $validated['password'];
        }

        $manager->fill($payload);
        $manager->save();

        return back()->with('success', 'Manager updated successfully.');
    }

    public function destroy(Request $request, User $manager): RedirectResponse
    {
        abort_unless(
            $request->user()?->hasRole('Admin') && $manager->hasRole('Manager'),
            403
        );

        $avatar = $this->nullableString($manager->getAttribute('avatar'));

        if ($avatar !== null && $avatar !== 'default.png') {
            Storage::disk('public')->delete($avatar);
        }

        User::destroy($manager->getKey());

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

        $currentAvatar = $this->nullableString($manager->getAttribute('avatar'));

        if ($currentAvatar !== null && $currentAvatar !== 'default.png') {
            Storage::disk('public')->delete($currentAvatar);
        }

        return $avatar->store('avatars', 'public');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeManager(User $manager, bool $includeCreatedAt = false): array
    {
        $payload = [
            'id' => $manager->getKey(),
            'name' => (string) $manager->getAttribute('name'),
            'email' => (string) $manager->getAttribute('email'),
            'country' => $this->nullableString($manager->getAttribute('country')),
            'gender' => $this->enumValue($manager->getAttribute('gender')),
            'avatar' => $this->nullableString($manager->getAttribute('avatar')),
        ];

        if ($includeCreatedAt) {
            $payload['created_at'] = $manager->getAttribute('created_at');
        }

        return $payload;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    private function enumValue(mixed $value): ?string
    {
        if ($value instanceof BackedEnum) {
            return is_string($value->value) ? $value->value : null;
        }

        return is_string($value) ? $value : null;
    }
}
