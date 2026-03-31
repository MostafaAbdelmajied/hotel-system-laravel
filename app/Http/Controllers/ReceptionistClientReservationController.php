<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReceptionistClientReservationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAssignedClientReservations', Reservation::class);

        /** @var User $user */
        $user = $request->user();

        $reservations = Reservation::query()
            ->select([
                'id',
                'user_id',
                'room_id',
                'accompany_number',
                'paid_price',
                'check_in',
                'check_out',
            ])
            ->whereHas('client', function (Builder $query) use ($user): void {
                $query->where('approved_by', $user->id)
                    ->where('status', UserStatus::Approved->value)
                    ->whereHas('roles', function (Builder $roleQuery): void {
                        $roleQuery->where('name', 'Client');
                    });
            })
            ->with([
                'client:id,name',
                'room:id,number',
            ])
            ->orderByDesc('check_in')
            ->paginate(10)
            ->withQueryString()
            ->through(function (Reservation $reservation): array {
                return [
                    'id' => $reservation->id,
                    'client' => [
                        'name' => $reservation->client?->name,
                    ],
                    'accompany_number' => $reservation->accompany_number,
                    'room' => [
                        'number' => $reservation->room?->number,
                    ],
                    'paid_price_cents' => $reservation->paid_price,
                    'check_in' => $reservation->check_in?->toDateString(),
                    'check_out' => $reservation->check_out?->toDateString(),
                ];
            });

        return Inertia::render('Reservations/ClientsReservations', [
            'reservations' => $reservations,
        ]);
    }
}
