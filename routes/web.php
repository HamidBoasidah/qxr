<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocaleController;


Route::middleware(['auth'])
    ->group(function () {
            
});

// روابط مصادقة لوحة التحكم (بدون حماية)
//Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
//Route::post('login', [AuthController::class, 'login'])->name('login');
//Route::post('logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/locale', LocaleController::class)->name('locale.set')->middleware('throttle:10,1');

// --- BREEZE MERGED CONTENT START ---
// Note: Duplicate imports and routes commented out to prevent conflicts.

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
// use Illuminate\Support\Facades\Route; // Already imported
// use Inertia\Inertia; // Already imported

/*
// Conflict: You already have a root route '/' defined above.
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Conflict: You already have a '/dashboard' route defined above.
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// --- BREEZE MERGED CONTENT END ---
