<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::resource('tickets', TicketController::class);

    Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store'])
        ->name('tickets.comments.store');

    Route::resource('users', UserController::class)
        ->middleware('role:Administrador');

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    Route::resource('categories', CategoryController::class)
    ->middleware('role:Administrador');
});

require __DIR__ . '/auth.php';