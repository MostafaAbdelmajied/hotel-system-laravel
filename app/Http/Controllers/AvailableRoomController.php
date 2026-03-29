<?php

namespace App\Http\Controllers;

use App\Http\Requests\AvailableRoomsRequest;
use App\Models\Room;
use Inertia\Inertia;
use Inertia\Response;

class AvailableRoomController extends Controller
{
    public function index(AvailableRoomsRequest $request): Response
    {
        $validated = $request->validated();

        $availableRooms = Room::query()
            ->with('floor:id,name')
            ->availableBetween($validated['check_in'], $validated['check_out'])
            ->orderBy('number')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Room $room): array => [
                'id' => $room->id,
                'number' => $room->number,
                'capacity' => $room->capacity,
                'price_cents' => $room->price,
                'price_in_dollars' => $room->price_in_dollars,
                'display_price' => '$'.$room->price_in_dollars,
                'floor_name' => $room->floor?->name,
            ]);

        return Inertia::render('Bookings/available-rooms', [
            'filters' => [
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
            ],
            'rooms' => $availableRooms,
        ]);
    }
}
