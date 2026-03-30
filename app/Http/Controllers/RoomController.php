<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\Floor;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $floorId = $this->resolveFloorId($request);
        $sort = $this->resolveSortKey((string) $request->input('sort', 'number'));
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';
        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50], true) ? $perPage : 10;
        $sortColumn = $this->resolveSortColumn($sort);

        $rooms = Room::query()
            ->select('rooms.*')
            ->with(['floor:id,name,number', 'creator:id,name'])
            ->leftJoin('floors', 'floors.id', '=', 'rooms.floor_id')
            ->leftJoin('users as creators', 'creators.id', '=', 'rooms.created_by')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('rooms.number', 'like', "%{$search}%")
                        ->orWhere('floors.name', 'like', "%{$search}%")
                        ->orWhere('floors.number', 'like', "%{$search}%")
                        ->orWhere('creators.name', 'like', "%{$search}%");
                });
            })
            ->when($floorId !== null, function (Builder $query) use ($floorId): void {
                $query->where('rooms.floor_id', $floorId);
            })
            ->orderBy($sortColumn, $direction)
            ->when($sortColumn !== 'rooms.number', function (Builder $query): void {
                $query->orderBy('rooms.number');
            })
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Room $room): array => [
                'id' => $room->id,
                'number' => $room->number,
                'capacity' => $room->capacity,
                'price' => $room->price,
                'price_in_dollars' => $room->price_in_dollars,
                'floor' => [
                    'id' => $room->floor?->id,
                    'name' => $room->floor?->name,
                    'number' => $room->floor?->number,
                ],
                'creator' => $room->creator === null ? null : [
                    'id' => $room->creator->id,
                    'name' => $room->creator->name,
                ],
                'created_by' => $room->created_by,
            ]);

        $floors = Floor::query()
            ->orderBy('number')
            ->get(['id', 'name', 'number']);

        return Inertia::render('Manager/Rooms/Index', [
            'rooms' => $rooms,
            'floors' => $floors,
            'filters' => [
                'search' => $search,
                'floor_id' => $floorId,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(StoreRoomRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        Room::query()->create([
            ...$this->roomPayload($request->validated()),
            'created_by' => $user->id,
        ]);

        return back()->with('success', 'Room created successfully.');
    }

    public function update(UpdateRoomRequest $request, Room $room): RedirectResponse
    {
        abort_unless($this->canManageRoom($request->user(), $room), 403);

        $room->update($this->roomPayload($request->validated()));

        return back()->with('success', 'Room updated successfully.');
    }

    public function destroy(Request $request, Room $room): RedirectResponse
    {
        abort_unless($this->canManageRoom($request->user(), $room), 403);

        if ($room->reservations()->exists()) {
            return back()->with('error', 'Cannot delete a room that has reservations.');
        }

        $room->delete();

        return back()->with('success', 'Room deleted successfully.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function roomPayload(array $validated): array
    {
        $validated['price'] = (int) round(((float) $validated['price']) * 100);

        return $validated;
    }

    private function canManageRoom(?User $user, Room $room): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasRole('Admin') || $room->created_by === $user->id;
    }

    private function resolveSortColumn(string $sort): string
    {
        return match ($sort) {
            'capacity' => 'rooms.capacity',
            'price' => 'rooms.price',
            'floor' => 'floors.number',
            'creator' => 'creators.name',
            default => 'rooms.number',
        };
    }

    private function resolveSortKey(string $sort): string
    {
        return in_array($sort, ['number', 'capacity', 'price', 'floor', 'creator'], true)
            ? $sort
            : 'number';
    }

    private function resolveFloorId(Request $request): ?int
    {
        if (! $request->filled('floor_id')) {
            return null;
        }

        $floorId = filter_var($request->input('floor_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $floorId === false ? null : $floorId;
    }
}
