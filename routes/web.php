<?php

use App\Http\Controllers\ClientApprovalController;
use App\Http\Controllers\MyApprovedClientController;
use App\Http\Controllers\PendingClientController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'approved', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::get('clients/pending', [PendingClientController::class, 'index'])->name('clients.pending');
    Route::get('clients/my-approved', [MyApprovedClientController::class, 'index'])->name('clients.my-approved');
    Route::patch('clients/{client}/approve', [ClientApprovalController::class, 'update'])->name('clients.approve');
});

require __DIR__.'/settings.php';
