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

