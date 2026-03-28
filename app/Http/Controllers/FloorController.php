<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFloorRequest;
use App\Http\Requests\UpdateFloorRequest;
use App\Models\Floor;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FloorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Floor::class);
        $floors = Floor::query()->with('creator:id,name', 'manager:id,name')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('number', 'like', "%{$search}%");
            })
            ->latest()->paginate(10)->withQueryString();

        return Inertia::render('Manager/Floors/Index', [
            'floors' => $floors,
            'managers' => User::role('Manager')->get(['id', 'name']),
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFloorRequest $request)
    {
        $this->authorize('create', Floor::class);

        $request->user()->createdFloors()->create($request->validated());

        return back()->with('success', 'Floor Created Successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFloorRequest $request, Floor $floor)
    {
        $this->authorize('update', $floor);

        $floor->update($request->validated());

        return back()->with('success', 'Floor Updated Successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Floor $floor)
    {
        $this->authorize('delete', $floor);

        $floor->delete();

        return back()->with('success', 'Floor Deleted Successfully.');
    }
}
