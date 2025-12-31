<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

// Public routes
Route::get('/health', fn() => response()->json(['status' => 'ok', 'timestamp' => now()]));
Route::get('/departments', [\App\Http\Controllers\Api\DepartmentController::class, 'index']); // Public for booking

// Auth routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
    Route::post('/forgot-password', [\App\Http\Controllers\Api\AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [\App\Http\Controllers\Api\AuthController::class, 'resetPassword']);
    Route::post('/activate', [\App\Http\Controllers\Api\AuthController::class, 'activate']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Current user
    Route::get('/me', fn(Request $request) => $request->user()->load('roles', 'permissions'));
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    
    // Tickets
    Route::apiResource('tickets', \App\Http\Controllers\Api\TicketController::class);
    Route::post('/tickets/{ticket}/accept', [\App\Http\Controllers\Api\TicketController::class, 'accept']);
    Route::post('/tickets/{ticket}/complete', [\App\Http\Controllers\Api\TicketController::class, 'complete']);
    
    // Departments
    Route::apiResource('departments', \App\Http\Controllers\Api\DepartmentController::class);
    
    // Users (Admin only)
    Route::apiResource('users', \App\Http\Controllers\Api\UserController::class);
    Route::post('/users/{user}/activate', [\App\Http\Controllers\Api\UserController::class, 'activate']);
    
    // Admin Dashboard
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/metrics', [\App\Http\Controllers\Api\AdminController::class, 'metrics']);
        Route::get('/audit-log', [\App\Http\Controllers\Api\AdminController::class, 'auditLog']);
    });
    
    // Chatbot
    Route::post('/chatbot/analyze', [\App\Http\Controllers\Api\ChatbotController::class, 'analyze']);
});
