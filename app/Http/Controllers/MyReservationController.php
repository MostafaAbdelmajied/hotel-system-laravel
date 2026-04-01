<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyReservationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewOwnReservations', Reservation::class);

        /** @var User $user */
        $user = $request->user();

        $reservations = Reservation::query()
            ->select([
                'id',
                'room_id',
                'accompany_number',
                'check_in',
                'check_out',
                'total_price',
            ])
            ->whereBelongsTo($user)
            ->with([
                'room:id,number',
                'payments'
            ])
            ->orderByDesc('check_in')
            ->paginate(10)
            ->withQueryString()
            ->through(function (Reservation $reservation): array {
                return [
                    'id' => $reservation->id,
                    'accompany_number' => $reservation->accompany_number,
                    'paid_price_cents' => $reservation->total_price,
                    'check_in' => $reservation->check_in?->toDateString(),
                    'check_out' => $reservation->check_out?->toDateString(),
                    'room' => [
                        'number' => $reservation->room?->number,
                    ],
                ];
            });

        return Inertia::render('Reservations/MyReservations', [
            'reservations' => $reservations,
        ]);
    }
}
