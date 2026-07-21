<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientPortalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EventContractController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventNoteController;
use App\Http\Controllers\EventTaskController;
use App\Http\Controllers\ExpenseConceptController;
use App\Http\Controllers\FinancialBalanceExportController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicReceiptController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ReceiptEmailController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierPayableController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/r/{token}', [PublicReceiptController::class, 'show'])
    ->name('receipts.public.show');

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:manage events')->group(function () {
        Route::get('/google-calendar/connect', [GoogleCalendarController::class, 'connect'])->name('google-calendar.connect');
        Route::get('/google-calendar/callback', [GoogleCalendarController::class, 'callback'])->name('google-calendar.callback');
        Route::put('/google-calendar/calendar', [GoogleCalendarController::class, 'selectCalendar'])->name('google-calendar.calendar');
        Route::delete('/google-calendar/disconnect', [GoogleCalendarController::class, 'disconnect'])->name('google-calendar.disconnect');
        Route::post('/events/{event}/google-calendar', [GoogleCalendarController::class, 'sync'])->name('events.google-calendar.sync');
        Route::delete('/events/{event}/google-calendar/link', [GoogleCalendarController::class, 'unlink'])->name('events.google-calendar.unlink');
        Route::delete('/events/{event}/google-calendar', [GoogleCalendarController::class, 'deleteRemote'])->name('events.google-calendar.delete');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:view dashboard')
        ->name('dashboard');

    Route::resource('users', UserController::class)
        ->except(['show'])
        ->middleware('permission:manage users');

    Route::resource('clients', ClientController::class)
        ->middleware('permission:manage clients');

    Route::get('/clients/{client}/balance.xlsx', [FinancialBalanceExportController::class, 'client'])
        ->middleware('permission:manage clients')
        ->name('clients.balance.export');

    Route::resource('services', ServiceController::class)
        ->middleware('permission:manage services');

    Route::patch('/suppliers/{supplier}/restore', [SupplierController::class, 'restore'])
        ->middleware('permission:manage suppliers')
        ->name('suppliers.restore');

    Route::resource('suppliers', SupplierController::class)
        ->middleware('permission:manage suppliers');

    Route::middleware('permission:manage payments')->group(function () {
        Route::patch('/supplier-payables/{supplier_payable}/cancel', [SupplierPayableController::class, 'cancel'])->name('supplier-payables.cancel');
        Route::get('/supplier-payables/{supplier_payable}/payment', [SupplierPayableController::class, 'paymentForm'])->name('supplier-payables.payment');
        Route::post('/supplier-payables/{supplier_payable}/payment', [SupplierPayableController::class, 'pay'])->name('supplier-payables.pay');
        Route::resource('supplier-payables', SupplierPayableController::class)->except(['destroy']);
    });

    Route::get('/expense-concepts/archived', [ExpenseConceptController::class, 'archived'])
        ->middleware('permission:manage expense concepts')
        ->name('expense-concepts.archived');
    Route::patch('/expense-concepts/{expense_concept}/restore', [ExpenseConceptController::class, 'restore'])
        ->middleware('permission:manage expense concepts')
        ->name('expense-concepts.restore');
    Route::resource('expense-concepts', ExpenseConceptController::class)
        ->middleware('permission:manage expense concepts');

    Route::get('/events/{event}/contract/create', [EventContractController::class, 'create'])
        ->middleware('permission:manage events')
        ->name('events.contracts.create');

    Route::post('/events/{event}/contract', [EventContractController::class, 'store'])
        ->middleware('permission:manage events')
        ->name('events.contracts.store');

    Route::resource('events', EventController::class)
        ->middleware('permission:manage events');

    Route::get('/events/{event}/balance.xlsx', [FinancialBalanceExportController::class, 'event'])
        ->middleware('permission:manage events')
        ->name('events.balance.export');

    Route::middleware('permission:manage quotations')->group(function () {
        Route::get('/quotations/{quotation}/pdf', [QuotationController::class, 'pdf'])->name('quotations.pdf');
        Route::patch('/quotations/{quotation}/status', [QuotationController::class, 'updateStatus'])->name('quotations.status.update');
        Route::resource('quotations', QuotationController::class);
    });

    Route::get('/transactions/{transaction}/pdf', [TransactionController::class, 'pdf'])
        ->middleware('permission:manage payments')
        ->name('transactions.pdf');

    Route::get('/transactions/{transaction}/email', [ReceiptEmailController::class, 'create'])
        ->middleware('permission:manage payments')
        ->name('transactions.email.create');

    Route::post('/transactions/{transaction}/email', [ReceiptEmailController::class, 'store'])
        ->middleware('permission:manage payments')
        ->name('transactions.email.store');

    Route::get('/expenses', [TransactionController::class, 'expenses'])
        ->middleware('permission:manage payments')
        ->name('expenses.index');

    Route::get('/incomes', [TransactionController::class, 'incomes'])
        ->middleware('permission:manage payments')
        ->name('incomes.index');

    Route::get('/transactions/{transaction}/proof', [TransactionController::class, 'downloadProof'])
        ->middleware('permission:manage payments')
        ->name('transactions.proof');

    Route::patch('/transactions/{transaction}/cancel', [TransactionController::class, 'cancel'])
        ->middleware('permission:manage payments')
        ->name('transactions.cancel');

    Route::resource('transactions', TransactionController::class)
        ->except(['destroy'])
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

    Route::get('/event-tasks/{eventTask}/edit', [EventTaskController::class, 'edit'])
        ->name('event-tasks.edit');
    Route::put('/event-tasks/{eventTask}', [EventTaskController::class, 'update'])
        ->name('event-tasks.update');
    Route::patch('/event-tasks/{eventTask}/complete', [EventTaskController::class, 'complete'])
        ->name('event-tasks.complete');
    Route::patch('/event-tasks/{eventTask}/cancel', [EventTaskController::class, 'cancel'])
        ->name('event-tasks.cancel');

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
