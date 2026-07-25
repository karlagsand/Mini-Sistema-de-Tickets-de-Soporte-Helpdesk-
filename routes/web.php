<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PriorityController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketAttachmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketStatusController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'role:Administrador,Agente,Usuario'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::post('/tickets/{ticket}/accept-solution', [TicketController::class, 'acceptSolution'])
        ->name('tickets.accept-solution');

    Route::post('/tickets/{ticket}/reopen', [TicketController::class, 'reopen'])
        ->name('tickets.reopen');

    Route::resource('tickets', TicketController::class)
        ->except(['edit']);

    Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store'])
        ->name('tickets.comments.store');

    Route::post('/tickets/{ticket}/attachments', [TicketAttachmentController::class, 'store'])
        ->name('tickets.attachments.store');

    Route::get('/attachments/{attachment}/preview', [TicketAttachmentController::class, 'preview'])
        ->name('tickets.attachments.preview');

    Route::get('/attachments/{attachment}/download', [TicketAttachmentController::class, 'download'])
        ->name('tickets.attachments.download');

    Route::delete('/attachments/{attachment}', [TicketAttachmentController::class, 'destroy'])
        ->name('tickets.attachments.destroy');

    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');

    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.read-all');

    Route::get('/notifications/pulse', [NotificationController::class, 'pulse'])
        ->name('notifications.pulse');

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    Route::middleware('role:Administrador')->group(function () {
        Route::get('/dashboard/admin/reporte', [DashboardController::class, 'adminReport'])
            ->name('dashboard.admin.report');

        Route::get('/dashboard/admin/reporte/pdf', [DashboardController::class, 'adminReportPdf'])
            ->name('dashboard.admin.report.pdf');

        Route::resource('users', UserController::class);
        Route::resource('categories', CategoryController::class);
        Route::resource('priorities', PriorityController::class);
        Route::resource('ticket-statuses', TicketStatusController::class);
    });
});

require __DIR__ . '/auth.php';
