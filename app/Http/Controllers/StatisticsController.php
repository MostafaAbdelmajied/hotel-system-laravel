<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StatisticsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if ($request->user()?->hasRole(['Admin', 'Manager'])) {
            return Inertia::render('Statistics', [
                'statisticsData' => $this->getStatisticsData(),
            ]);
        }

        return Inertia::render('Statistics');
    }

    private function getStatisticsData()
    {

        $genderData = User::query()
            ->join('reservations', 'users.id', '=', 'reservations.user_id')
            ->select('users.gender', DB::raw('count(*) as total'))
            ->groupBy('users.gender')
            ->get();
        $revenueData = Reservation::query()->whereYear('reservations.created_at', \Illuminate\Support\now()->year)
            ->select(DB::raw('Month(created_at) as month'), DB::raw('SUM(paid_price) as total'))
            ->groupBy('month')->orderBy('month')
            ->get();
        $countryData = User::query()
            ->join('reservations', 'users.id', '=', 'reservations.user_id')
            ->select('users.country', DB::raw('count(*) as total'))
            ->groupBy('users.country')
            ->get();
        $topClients = User::query()
            ->join('reservations', 'users.id', '=', 'reservations.user_id')
            ->select('users.name', DB::raw('count(*) as total'))
            ->groupBy('users.name', 'users.id')
            ->orderByDesc('total')->limit(10)->get();

        return [
            'genderData' => $genderData,
            'revenueData' => $revenueData,
            'countryData' => $countryData,
            'topClients' => $topClients,
        ];
    }
}
