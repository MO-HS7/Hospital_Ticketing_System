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
    
    // Users (Admin only)
    Route::apiResource('users', \App\Http\Controllers\Api\UserController::class);
    Route::post('/users/{user}/activate', [\App\Http\Controllers\Api\UserController::class, 'activate']);
    
    // Admin Dashboard & Admin-only routes
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/metrics', [\App\Http\Controllers\Api\AdminController::class, 'metrics']);
        Route::get('/audit-log', [\App\Http\Controllers\Api\AdminController::class, 'auditLog']);
        
        // Admin Departments CRUD
        Route::get('/departments', [\App\Http\Controllers\Api\DepartmentController::class, 'adminIndex']);
        Route::get('/departments/stats', [\App\Http\Controllers\Api\DepartmentController::class, 'stats']);
        Route::post('/departments', [\App\Http\Controllers\Api\DepartmentController::class, 'store']);
        Route::get('/departments/{department}', [\App\Http\Controllers\Api\DepartmentController::class, 'show']);
        Route::put('/departments/{department}', [\App\Http\Controllers\Api\DepartmentController::class, 'update']);
        Route::delete('/departments/{department}', [\App\Http\Controllers\Api\DepartmentController::class, 'destroy']);
    });
    
    // Chatbot
    Route::post('/chatbot/analyze', [\App\Http\Controllers\Api\ChatbotController::class, 'analyze']);
});
