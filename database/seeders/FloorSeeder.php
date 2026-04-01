<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\Floor;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class FloorSeeder extends Seeder
{
    public function run(): void
    {
        $managers = User::query()
            ->role('Manager')
            ->where('status', UserStatus::Approved)
            ->orderBy('email')
            ->get();

        if ($managers->count() < 3) {
            throw new RuntimeException('FloorSeeder expects at least three approved managers. Run RolesAndPermissionsSeeder and StaffAccountsSeeder first.');
        }

        $floors = [
            [
                'name' => 'Ground Floor',
                'number' => '01',
                'created_by' => $managers[0]->id,
                'managed_by' => $managers[0]->id,
            ],
            [
                'name' => 'First Floor',
                'number' => '02',
                'created_by' => $managers[1]->id,
                'managed_by' => $managers[1]->id,
            ],
            [
                'name' => 'Second Floor',
                'number' => '03',
                'created_by' => $managers[2]->id,
                'managed_by' => $managers[2]->id,
            ],
        ];

        foreach ($floors as $floor) {
            Floor::query()->updateOrCreate(
                ['number' => $floor['number']],
                $floor,
            );
        }
    }
}
