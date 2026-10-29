<?php

use App\Http\Controllers\CashierController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\WorkshopCheckInController;
use App\Http\Controllers\WorkshopController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', function () {
    return redirect()->route('tracking.index');
});

Route::get('/tracking', [TrackingController::class, 'index'])->name('tracking.index');

// Protected Routes (Internal Staff & Admin)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        if ($user && $user->role === 'cashier') {
            return redirect()->route('cashier.index');
        }

        return redirect()->route('workshop.index');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Kasir / Front Desk
    Route::get('/cashier', [CashierController::class, 'index'])->name('cashier.index');
    Route::post('/cashier/items/{item}/handover', [CashierController::class, 'handover'])->name('cashier.items.handover');

    // Workshop Floor (Kanban Board)
    Route::get('/workshop', [WorkshopController::class, 'index'])->name('workshop.index');
    Route::patch('/workshop/items/{item}/status', [WorkshopController::class, 'updateStatus'])->name('workshop.items.update-status');
    Route::post('/workshop/station-assignments', [WorkshopController::class, 'updateStationAssignments'])->name('workshop.station-assignments.update');

    // Workshop Check-In & Intake Kasir
    Route::get('/workshop/checkin', [WorkshopCheckInController::class, 'create'])->name('workshop.checkin');
    Route::post('/workshop/checkin', [WorkshopCheckInController::class, 'store'])->name('workshop.checkin.store');
    Route::get('/workshop/checkin/find-customer', [WorkshopCheckInController::class, 'findCustomer'])->name('workshop.checkin.find-customer');
});

require __DIR__.'/auth.php';
