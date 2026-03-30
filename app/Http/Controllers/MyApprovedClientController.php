<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MyApprovedClientController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewMyApprovedClients', User::class);

        $approvedClients = $this->approvedClientsQuery($request->user())
            ->orderByDesc('approved_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('clients/my-approved-clients', [
            'approvedClients' => $approvedClients,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authorize('viewMyApprovedClients', User::class);

        $approvedClients = $this->approvedClientsQuery($user)
            ->orderByDesc('approved_at')
            ->cursor();

        return response()->streamDownload(function () use ($approvedClients): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Name', 'Email', 'Mobile Number', 'Country', 'Gender', 'Approved At']);

            foreach ($approvedClients as $client) {
                fputcsv($handle, [
                    $this->sanitizeCsvValue($client->name),
                    $this->sanitizeCsvValue($client->email),
                    $this->sanitizeCsvValue($client->mobile_number),
                    $this->sanitizeCsvValue($client->country),
                    $this->sanitizeCsvValue($client->gender?->value ?? ''),
                    $client->approved_at?->toDateTimeString() ?? '',
                ]);
            }

            fclose($handle);
        }, 'approved-clients-'.now()->toDateString().'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function approvedClientsQuery(User $user): Builder
    {
        return User::query()
            ->select([
                'id',
                'name',
                'email',
                'mobile_number',
                'country',
                'gender',
                'approved_at',
            ])
            ->where('status', UserStatus::Approved->value)
            ->when(
                ! $user->hasRole('Admin'),
                fn (Builder $query) => $query->where('approved_by', $user->id)
            )
            ->whereHas('roles', function (Builder $query): void {
                $query->where('name', 'Client');
            });
    }

    private function sanitizeCsvValue(?string $value): string
    {
        $string = (string) ($value ?? '');

        if (preg_match('/^[=\-+@]/', $string) === 1) {
            return "'".$string;
        }

        return $string;
    }
}
