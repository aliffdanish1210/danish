<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ProfileController;
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


Route::middleware('auth')->group(function () {
    // Dashboard / Home
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/logout', [ProfileController::class, 'logout'])->name('logout');
    // Event CRUD (Create, Read, Update, Delete)
    Route::resource('events', EventController::class);
});

Route::middleware(['auth', 'role:admin'])->group(function () {

    Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');

    Route::delete('/admin/users/{id}', [AdminController::class, 'destroyUser'])
        ->name('admin.users.destroy');

    Route::delete('/admin/events/{id}', [AdminController::class, 'destroyEvent'])
        ->name('admin.events.destroy');
});
