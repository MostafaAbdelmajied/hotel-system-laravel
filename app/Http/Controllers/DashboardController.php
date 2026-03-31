<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Enums\UserStatus;
use App\Models\Floor;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if ($request->user()?->hasRole('Admin')) {
            return Inertia::render('Admin/Dashboard', [
                'dashboardData' => $this->adminDashboardData(),
            ]);
        }

        return Inertia::render('Dashboard');
    }

    /**
     * @return array<string, mixed>
     */
    private function adminDashboardData(): array
    {
        $today = CarbonImmutable::today(config('app.timezone'));

        $pendingClientsCount = User::query()
            ->whereHas('roles', function ($query): void {
                $query->where('name', 'Client');
            })
            ->where('status', UserStatus::Pending->value)
            ->count();

        $totalClientsCount = User::query()
            ->whereHas('roles', function ($query): void {
                $query->where('name', 'Client');
            })
            ->count();

        $totalManagersCount = User::query()
            ->whereHas('roles', function ($query): void {
                $query->where('name', 'Manager');
            })
            ->count();

        $totalReceptionistsCount = User::query()
            ->whereHas('roles', function ($query): void {
                $query->where('name', 'Receptionist');
            })
            ->count();

        $floorsCount = Floor::query()->count();
        $roomsCount = Room::query()->count();

        $activeReservationsCount = Reservation::query()
            ->where('status', '!=', ReservationStatus::CANCELLED->value)
            ->whereDate('check_in', '<=', $today->toDateString())
            ->whereDate('check_out', '>', $today->toDateString())
            ->count();

        $occupiedRoomIdsCount = Reservation::query()
            ->where('status', '!=', ReservationStatus::CANCELLED->value)
            ->whereDate('check_in', '<=', $today->toDateString())
            ->whereDate('check_out', '>', $today->toDateString())
            ->distinct('room_id')
            ->count('room_id');

        $availableRoomsCount = max($roomsCount - $occupiedRoomIdsCount, 0);

        return [
            'pending_actions' => [
                'pending_clients' => [
                    'count' => $pendingClientsCount,
                    'label' => 'Pending Clients',
                    'description' => 'Clients waiting approval',
                    'href' => route('clients.pending'),
                ],
            ],
            'system_overview' => [
                'total_clients' => $totalClientsCount,
                'total_managers' => $totalManagersCount,
                'total_receptionists' => $totalReceptionistsCount,
            ],
            'quick_stats' => [
                'active_reservations' => $activeReservationsCount,
                'available_rooms' => $availableRoomsCount,
                'total_rooms' => $roomsCount,
                'total_floors' => $floorsCount,
            ],
            'quick_actions' => [
                [
                    'label' => '+ Create Manager',
                    'enabled' => false,
                    'href' => null,
                ],
                [
                    'label' => '+ Create Receptionist',
                    'enabled' => false,
                    'href' => null,
                ],
            ],
            'recent_activity' => $this->recentAdminActivities(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentAdminActivities(): array
    {
        $reservationActivities = Reservation::query()
            ->with(['user:id,name', 'room:id,number'])
            ->latest()
            ->limit(6)
            ->get()
            ->map(function (Reservation $reservation): array {
                $amount = '$'.number_format($reservation->paid_price / 100, 2);
                $isCancelled = $reservation->status === ReservationStatus::CANCELLED;
                $roomNumber = $reservation->room?->number ?? 'N/A';

                return [
                    'user' => $reservation->user?->name ?? 'Unknown user',
                    'action' => $isCancelled
                        ? "Cancelled reservation for room {$roomNumber}"
                        : "Booked room {$roomNumber}",
                    'time' => $reservation->created_at?->diffForHumans() ?? 'Recently',
                    'amount' => $isCancelled ? '-'.$amount : $amount,
                    'positive' => ! $isCancelled,
                    'occurred_at' => $reservation->created_at?->timestamp ?? 0,
                ];
            });

        $approvalActivities = User::query()
            ->whereHas('roles', function ($query): void {
                $query->where('name', 'Client');
            })
            ->where('status', UserStatus::Approved->value)
            ->whereNotNull('approved_at')
            ->with('approvedBy:id,name')
            ->latest('approved_at')
            ->limit(6)
            ->get()
            ->map(function (User $client): array {
                $approverName = $client->approvedBy?->name;

                return [
                    'user' => $client->name,
                    'action' => $approverName !== null
                        ? "Approved by {$approverName}"
                        : 'Client account approved',
                    'time' => $client->approved_at?->diffForHumans() ?? 'Recently',
                    'amount' => null,
                    'positive' => true,
                    'occurred_at' => $client->approved_at?->timestamp ?? 0,
                ];
            });

        return $reservationActivities
            ->concat($approvalActivities)
            ->sortByDesc('occurred_at')
            ->take(6)
            ->values()
            ->map(fn (array $activity): array => [
                'user' => $activity['user'],
                'action' => $activity['action'],
                'time' => $activity['time'],
                'amount' => $activity['amount'],
                'positive' => $activity['positive'],
            ])
            ->all();
    }

    private function formatCurrency(int $amountInCents): string
    {
        return '$'.number_format($amountInCents / 100, 2);
    }

    private function formatTrend(int $currentValue, int $previousValue): string
    {
        if ($previousValue === 0) {
            return $currentValue > 0 ? '+100.0%' : '0.0%';
        }

        $percentage = (($currentValue - $previousValue) / $previousValue) * 100;

        return sprintf('%+.1f%%', $percentage);
    }

    private function formatTrendSentence(int $currentValue, int $previousValue): string
    {
        if ($previousValue === 0) {
            return $currentValue > 0
                ? 'Revenue started moving this month.'
                : 'No revenue recorded yet.';
        }

        $difference = $currentValue - $previousValue;

        if ($difference === 0) {
            return 'Revenue matches last month.';
        }

        return $difference > 0
            ? 'Revenue is up from last month.'
            : 'Revenue is below last month.';
    }
}
