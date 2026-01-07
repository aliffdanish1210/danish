<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AuditLogController;
use App\Models\User;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to dashboard/home
Route::get('/', function () {
    return redirect()->route('home');
});

// Authentication routes (login, register, logout)
Auth::routes();
Auth::routes(['verify' => true]);


Route::middleware('auth')->group(function () {
    // Dashboard / Home
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/logout', [ProfileController::class, 'logout'])->name('logout');
    // Event CRUD (Create, Read, Update, Delete)
    Route::resource('events', EventController::class);
    // Audit Logs
     Route::get('/audit-logs', [AuditLogController::class, 'index'])
        ->name('audit.logs');

    Route::get('/audit-logs/{id}', [AuditLogController::class, 'show'])
        ->name('audit.logs.show');

    Route::get('/audit-logs-export', [AuditLogController::class, 'export'])
        ->name('audit.logs.export');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    
    // Dashboard with search
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    
    // Users management
    Route::patch('/users/{id}/deactivate', [AdminController::class, 'deactivateUser'])->name('users.deactivate');
    Route::patch('/users/{id}/activate', [AdminController::class, 'activateUser'])->name('users.activate');
    Route::delete('/users/{id}', [AdminController::class, 'destroyUser'])->name('users.destroy');
    Route::get('/users/export', [AdminController::class, 'exportUsers'])->name('users.export');
    
    // Events management
    Route::delete('/events/{id}', [AdminController::class, 'destroyEvent'])->name('events.destroy');
    Route::get('/events/export', [AdminController::class, 'exportEvents'])->name('events.export');
    
    // API endpoints for AJAX search (optional)
    Route::get('/api/users/search', [AdminController::class, 'searchUsers'])->name('api.users.search');
    Route::get('/api/events/search', [AdminController::class, 'searchEvents'])->name('api.events.search');
    Route::get('/api/stats', [AdminController::class, 'getStats'])->name('api.stats');
    
});
