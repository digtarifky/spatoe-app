<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\WorkshopController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public Routes
Route::get('/', function () {
    return redirect()->route('tracking.index');
});

Route::get('/tracking', [TrackingController::class, 'index'])->name('tracking.index');

// Protected Routes (Internal Staff & Admin)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Workshop Floor (Kanban Board)
    Route::get('/workshop', [WorkshopController::class, 'index'])->name('workshop.index');
    Route::patch('/workshop/items/{item}/status', [WorkshopController::class, 'updateStatus'])->name('workshop.items.update-status');
});

require __DIR__.'/auth.php';
