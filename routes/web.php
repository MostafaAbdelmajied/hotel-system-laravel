<?php

use App\Http\Controllers\AvailableRoomController;
use App\Http\Controllers\ClientApprovalController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\MyApprovedClientController;
use App\Http\Controllers\PendingClientController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::post('stripe/webhook', [ReservationController::class, 'stripeWebhook'])->name('stripe.webhook');

Route::middleware(['auth', 'approved', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::get('bookings/available-rooms', [AvailableRoomController::class, 'index'])->name('bookings.available-rooms');
    Route::get('reservations/rooms/{room}', [ReservationController::class, 'show'])->name('reservations.rooms.show');
    Route::post('reservations/rooms/{room}/start-payment', [ReservationController::class, 'startPayment'])->name('reservations.rooms.start-payment');
    Route::get('reservations/payment/success', [ReservationController::class, 'paymentSuccess'])->name('reservations.payment.success');
    Route::get('reservations/payment/cancel', [ReservationController::class, 'paymentCancel'])->name('reservations.payment.cancel');
    Route::get('clients/pending', [PendingClientController::class, 'index'])->name('clients.pending');
    Route::get('clients/my-approved', [MyApprovedClientController::class, 'index'])->name('clients.my-approved');
    Route::patch('clients/{client}/approve', [ClientApprovalController::class, 'update'])->name('clients.approve');
});
Route::middleware(['auth', 'approved', 'verified', 'role:Manager|Admin'])
    ->prefix('manager')
    ->name('manager.')
    ->group(function () {
        Route::resource('rooms', RoomController::class)
            ->except(['create', 'edit', 'show']);
        Route::resource('floors', FloorController::class)->except(['create', 'edit', 'show']);
    });
require __DIR__.'/settings.php';
