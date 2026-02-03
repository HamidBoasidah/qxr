<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {

    Route::put('/update-password', [App\Http\Controllers\Api\AuthController::class, 'updatePassword']);

    Route::apiResource('addresses', App\Http\Controllers\Api\AddressController::class);
    Route::post('addresses/{address}/activate', [App\Http\Controllers\Api\AddressController::class, 'activate']);
    Route::post('addresses/{address}/deactivate', [App\Http\Controllers\Api\AddressController::class, 'deactivate']);
    Route::post('addresses/{address}/set-default', [App\Http\Controllers\Api\AddressController::class, 'setDefault']);

    // Categories
    Route::get('/categories', [App\Http\Controllers\Api\CategoryController::class, 'index']);
    Route::get('/categories/type/{type}', [App\Http\Controllers\Api\CategoryController::class, 'byType']);
    
    // Tags
    Route::get('/tags', [App\Http\Controllers\Api\TagController::class, 'index']);
    Route::get('/tags/type/{type}', [App\Http\Controllers\Api\TagController::class, 'byType']);
    
    // Conversations
    Route::get('conversations', [App\Http\Controllers\Api\ConversationController::class, 'index']);
    Route::post('conversations', [App\Http\Controllers\Api\ConversationController::class, 'store']);
    Route::post('conversations/{conversation}/read', [App\Http\Controllers\Api\ConversationController::class, 'markAsRead']);

    // Messages
    Route::get('conversations/{conversation}/messages', [App\Http\Controllers\Api\MessageController::class, 'index']);
    Route::post('conversations/{conversation}/messages', [App\Http\Controllers\Api\MessageController::class, 'store']);
});



Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/logout-all-devices', [App\Http\Controllers\Api\AuthController::class, 'logoutFromAllDevices'])->middleware('auth:sanctum');
Route::get('/me', [App\Http\Controllers\Api\AuthController::class, 'me'])->middleware('auth:sanctum');
Route::post('/register', [App\Http\Controllers\Api\RegisteredUserController::class, 'store']);