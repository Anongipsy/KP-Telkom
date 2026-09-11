<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

// Authenticated Routes
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/refresh', [DashboardController::class, 'refresh'])->name('dashboard.refresh');

    // Contracts & Pipeline — PRD FR-03, FR-04, FR-05, FR-06, FR-07
    Route::prefix('contracts')->name('contracts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ContractController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\ContractController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\ContractController::class, 'store'])->name('store');
        Route::get('/{lop}', [\App\Http\Controllers\ContractController::class, 'show'])->name('show');
        Route::get('/{lop}/edit', [\App\Http\Controllers\ContractController::class, 'edit'])->name('edit');
        Route::put('/{lop}', [\App\Http\Controllers\ContractController::class, 'update'])->name('update');
        Route::post('/{lop}/complete', [\App\Http\Controllers\ContractController::class, 'complete'])->name('complete');

        // Google Drive Document Viewer — PRD FR-12 & FR-13
        Route::get('/{lop}/document/metadata', [\App\Http\Controllers\DocumentController::class, 'metadata'])->name('document.metadata');
        Route::get('/{lop}/document/proxy', [\App\Http\Controllers\DocumentController::class, 'proxy'])->name('document.proxy');
    });

    // Early Warning & Notifications — PRD FR-09, FR-10, FR-11
    Route::get('/monitoring', [\App\Http\Controllers\MonitoringController::class, 'index'])->name('monitoring.index');

    // Notifications actions — PRD FR-11
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::post('/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::delete('/clear-all', [\App\Http\Controllers\NotificationController::class, 'clearAll'])->name('clear-all');
        Route::delete('/{notification}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('destroy');
    });

    // Admin & Developer Protected Routes — PRD FR-02 & Section 4.2
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-logs');
        Route::get('/', function () {
            return response()->json([
                'status' => 'success',
                'message' => 'Welcome to Admin / Developer Control Panel',
                'user' => auth()->user()->only(['id', 'name', 'email', 'role']),
            ]);
        })->name('index');
    });
});
