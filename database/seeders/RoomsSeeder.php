<?php

namespace Database\Seeders;

use App\Models\Floor;
use App\Models\Room;
use Illuminate\Database\Seeder;
use RuntimeException;

class RoomsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $floors = Floor::query()
            ->orderBy('number')
            ->get()
            ->keyBy('number');

        $roomBlueprints = [
            '01' => [
                ['number' => '0101', 'capacity' => 1, 'price' => 8500],
                ['number' => '0102', 'capacity' => 2, 'price' => 12000],
                ['number' => '0103', 'capacity' => 3, 'price' => 16500],
                ['number' => '0104', 'capacity' => 4, 'price' => 21000],
            ],
            '02' => [
                ['number' => '0201', 'capacity' => 1, 'price' => 9500],
                ['number' => '0202', 'capacity' => 2, 'price' => 13500],
                ['number' => '0203', 'capacity' => 3, 'price' => 18000],
                ['number' => '0204', 'capacity' => 4, 'price' => 23000],
            ],
            '03' => [
                ['number' => '0301', 'capacity' => 2, 'price' => 14500],
                ['number' => '0302', 'capacity' => 2, 'price' => 15500],
                ['number' => '0303', 'capacity' => 3, 'price' => 20500],
                ['number' => '0304', 'capacity' => 5, 'price' => 28000],
            ],
        ];

        foreach ($roomBlueprints as $floorNumber => $rooms) {
            $floor = $floors->get($floorNumber);

            if ($floor === null) {
                throw new RuntimeException("RoomsSeeder expects floor {$floorNumber} to exist. Run FloorSeeder first.");
            }

            foreach ($rooms as $room) {
                Room::query()->updateOrCreate(
                    ['number' => $room['number']],
                    [
                        ...$room,
                        'floor_id' => $floor->id,
                        'created_by' => $floor->created_by ?? $floor->managed_by,
                    ],
                );
            }
        }
    }
}
