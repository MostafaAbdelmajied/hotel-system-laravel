<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PendingClientController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewPendingClients', User::class);

        $pendingClients = $this->pendingClientsQuery()
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('clients/pending-clients', [
            'pendingClients' => $pendingClients,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewPendingClients', User::class);

        $pendingClients = $this->pendingClientsQuery()
            ->orderByDesc('created_at')
            ->cursor();

        return response()->streamDownload(function () use ($pendingClients): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Name', 'Email', 'Country', 'Gender', 'Registered At']);

            foreach ($pendingClients as $client) {
                fputcsv($handle, [
                    $this->sanitizeCsvValue($client->name),
                    $this->sanitizeCsvValue($client->email),
                    $this->sanitizeCsvValue($client->country),
                    $this->sanitizeCsvValue($client->gender?->value ?? ''),
                    $client->created_at?->toDateTimeString() ?? '',
                ]);
            }

            fclose($handle);
        }, 'pending-clients-'.now()->toDateString().'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function pendingClientsQuery(): Builder
    {
        return User::query()
            ->select([
                'id',
                'name',
                'email',
                'country',
                'gender',
                'created_at',
            ])
            ->where('status', UserStatus::Pending->value)
            ->role('Client');
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
