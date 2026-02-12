<?php

use App\Http\Controllers\Company\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Company\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Company\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Company\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Company\Auth\PasswordController;
use App\Http\Controllers\Company\Auth\VerifyEmailController;
use App\Http\Controllers\Company\AddressController;
use App\Http\Controllers\Company\ConversationController;
use App\Http\Controllers\Company\MessageController;
use App\Http\Controllers\Company\UserController;
use App\Http\Controllers\Company\ProductController;
use App\Http\Controllers\Company\Auth\ProfileController;
use App\Http\Controllers\Company\DashboardController;
use Illuminate\Support\Facades\Route;

// Company users use the default auth routes at /login
// No separate guest routes needed for company - they use the main auth routes

Route::middleware(['auth:web', 'company'])
    ->prefix('company')
    ->as('company.')
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        // Profile
        Route::get('/profile', [ProfileController::class, 'show'])
            ->name('profile');

        Route::patch('/profile', [ProfileController::class, 'update'])
            ->name('profile.update');

        Route::post('/profile', [ProfileController::class, 'update'])
            ->name('profile.update.post');

        // Addresses
        Route::resource('addresses', AddressController::class)
            ->names('addresses');

        Route::patch('addresses/{id}/activate', [AddressController::class, 'activate'])
            ->name('addresses.activate');

        Route::patch('addresses/{id}/deactivate', [AddressController::class, 'deactivate'])
            ->name('addresses.deactivate');

        Route::patch('addresses/{id}/set-default', [AddressController::class, 'setDefault'])
            ->name('addresses.setDefault');


        // Products
        Route::resource('products', ProductController::class)
            ->names('products');

        Route::patch('products/{id}/activate', [ProductController::class, 'activate'])
            ->name('products.activate');

        Route::patch('products/{id}/deactivate', [ProductController::class, 'deactivate'])
            ->name('products.deactivate');

        // Offers
        Route::resource('offers', \App\Http\Controllers\Company\OfferController::class)
            ->names('offers');

        // Users (for company to manage their own users if needed)
        Route::resource('users', UserController::class)
            ->names('users');

        Route::patch('users/{id}/activate', [UserController::class, 'activate'])
            ->name('users.activate');

        Route::patch('users/{id}/deactivate', [UserController::class, 'deactivate'])
            ->name('users.deactivate');

        // Chat
        Route::prefix('chat')->as('chat.')->group(function () {
            // Page views (Inertia)
            Route::get('/conversations', [ConversationController::class, 'index'])
                ->name('conversations.index');
            
            Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])
                ->name('conversations.show');
            
            // Actions (JSON)
            Route::post('/conversations', [ConversationController::class, 'store'])
                ->name('conversations.store');
            
            Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])
                ->name('messages.store');
            
            // Optional features
            Route::post('/conversations/{conversation}/read', [MessageController::class, 'markAsRead'])
                ->name('conversations.read');
            
            Route::post('/messages/upload', [MessageController::class, 'upload'])
                ->name('messages.upload');
        });


        Route::get('verify-email', EmailVerificationPromptController::class)
            ->name('verification.notice');

        Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');

        Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('verification.send');

        Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
            ->name('password.confirm');

        Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

        Route::put('password', [PasswordController::class, 'update'])->name('password.update');

        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
            ->name('logout');
});
