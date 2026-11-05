<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AuditLogController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to dashboard/home
Route::get('/', function () {
    return redirect()->route('home');
});

// Authentication routes (login, register, logout, verify)
Auth::routes(['verify' => true]);

// Authenticated Users (Admins & Regular Users)
Route::middleware('auth')->group(function () {
    // Dashboard / Home
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/logout', [ProfileController::class, 'logout'])->name('logout');

    // Event CRUD
    Route::resource('events', EventController::class);

    // Audit Logs
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.logs');
    Route::get('/audit-logs/{id}', [AuditLogController::class, 'show'])->name('audit.logs.show');
    Route::get('/audit-logs-export', [AuditLogController::class, 'export'])->name('audit.logs.export');
});

// Admin-Only Routes
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Admin Dashboard
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');

    // Users Management
    Route::patch('/users/{id}/deactivate', [AdminController::class, 'deactivateUser'])->name('users.deactivate');
    Route::patch('/users/{id}/activate', [AdminController::class, 'activateUser'])->name('users.activate');
    Route::delete('/users/{id}', [AdminController::class, 'destroyUser'])->name('users.destroy');
    Route::get('/users/export', [AdminController::class, 'exportUsers'])->name('users.export');

    // Admin Event Management
    Route::delete('/admin/events/{id}', [AdminController::class, 'destroyEvent'])->name('admin.events.destroy');
    Route::get('/admin/events/export', [AdminController::class, 'exportEvents'])->name('events.export');

    // API endpoints for AJAX search
    Route::get('/api/users/search', [AdminController::class, 'searchUsers'])->name('api.users.search');
    Route::get('/api/events/search', [AdminController::class, 'searchEvents'])->name('api.events.search');
    Route::get('/api/stats', [AdminController::class, 'getStats'])->name('api.stats');
});