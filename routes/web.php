<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ClientPortalController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventNoteController;
use App\Http\Controllers\EventTaskController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:view dashboard')
        ->name('dashboard');

    Route::resource('users', UserController::class)
        ->except(['show'])
        ->middleware('permission:manage users');

    Route::resource('clients', ClientController::class)
        ->middleware('permission:manage clients');

    Route::resource('services', ServiceController::class)
        ->middleware('permission:manage services');

    Route::resource('events', EventController::class)
        ->middleware('permission:manage events');

    Route::resource('quotations', QuotationController::class)
        ->middleware('permission:manage quotations');

    Route::resource('payments', PaymentController::class)
        ->middleware('permission:manage payments');

    Route::resource('documents', DocumentController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy'])
        ->middleware('permission:manage documents');

    Route::get('/calendar', [CalendarController::class, 'index'])
        ->middleware('permission:view calendar')
        ->name('calendar.index');

    Route::get('/calendar/feed', [CalendarController::class, 'feed'])
        ->middleware('permission:view calendar')
        ->name('calendar.feed');

    Route::post('/events/{event}/tasks', [EventTaskController::class, 'store'])
        ->middleware('permission:manage events')
        ->name('events.tasks.store');

    Route::delete('/event-tasks/{eventTask}', [EventTaskController::class, 'destroy'])
        ->middleware('permission:manage events')
        ->name('events.tasks.destroy');

    Route::post('/events/{event}/notes', [EventNoteController::class, 'store'])
        ->middleware('permission:manage events')
        ->name('events.notes.store');

    Route::delete('/event-notes/{eventNote}', [EventNoteController::class, 'destroy'])
        ->middleware('permission:manage events')
        ->name('events.notes.destroy');

    Route::get('/portal', [ClientPortalController::class, 'index'])
        ->middleware('permission:access client portal')
        ->name('client.portal');

    Route::get('/logout', function () {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout.get');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';