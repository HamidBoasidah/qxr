<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {

    Route::put('/update-password', [App\Http\Controllers\Api\AuthController::class, 'updatePassword']);

    // Location endpoints: المحافظات - المديريات - المناطق
    Route::get('locations/governorates', [App\Http\Controllers\Api\LocationController::class, 'governorates']);
    Route::get('locations/governorates/{id}/districts', [App\Http\Controllers\Api\LocationController::class, 'districts']);
    Route::get('locations/districts/{id}/areas', [App\Http\Controllers\Api\LocationController::class, 'areas']);

    Route::apiResource('addresses', App\Http\Controllers\Api\AddressController::class);
    Route::post('addresses/{address}/activate', [App\Http\Controllers\Api\AddressController::class, 'activate']);
    Route::post('addresses/{address}/deactivate', [App\Http\Controllers\Api\AddressController::class, 'deactivate']);
    Route::post('addresses/{address}/set-default', [App\Http\Controllers\Api\AddressController::class, 'setDefault']);

    // Tags
    Route::get('/tags', [App\Http\Controllers\Api\TagController::class, 'index']);
    Route::get('/tags/type/{type}', [App\Http\Controllers\Api\TagController::class, 'byType']);

    // Products helper endpoints (must come before resource to avoid route parameter collision)
    Route::get('products/categories', [App\Http\Controllers\Api\ProductController::class, 'categories']);
    Route::get('products/tags', [App\Http\Controllers\Api\ProductController::class, 'tags']);
    Route::get('products/mine', [App\Http\Controllers\Api\ProductController::class, 'mine']);

    // Products resource (عرض فقط)
    Route::apiResource('products', App\Http\Controllers\Api\ProductController::class);

    Route::post('products/{product}/activate', [App\Http\Controllers\Api\ProductController::class, 'activate']);
    Route::post('products/{product}/deactivate', [App\Http\Controllers\Api\ProductController::class, 'deactivate']);
    
    
    // Conversations
    Route::get('conversations', [App\Http\Controllers\Api\ConversationController::class, 'index']);
    Route::post('conversations', [App\Http\Controllers\Api\ConversationController::class, 'store']);
    Route::post('conversations/{conversation}/read', [App\Http\Controllers\Api\ConversationController::class, 'markAsRead']);

    // Messages
    Route::get('conversations/{conversation}/messages', [App\Http\Controllers\Api\MessageController::class, 'index']);
    Route::post('conversations/{conversation}/messages', [App\Http\Controllers\Api\MessageController::class, 'store']);
});

// Categories
Route::get('/categories', [App\Http\Controllers\Api\CategoryController::class, 'index']);
Route::get('/categories/type/{type}', [App\Http\Controllers\Api\CategoryController::class, 'byType']);
    

Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/logout-all-devices', [App\Http\Controllers\Api\AuthController::class, 'logoutFromAllDevices'])->middleware('auth:sanctum');
Route::get('/me', [App\Http\Controllers\Api\AuthController::class, 'me'])->middleware('auth:sanctum');
Route::post('/register', [App\Http\Controllers\Api\RegisteredUserController::class, 'store']);