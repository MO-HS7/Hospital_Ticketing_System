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
Route::get('/health', \App\Http\Controllers\Api\HealthController::class);
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
    Route::post('/tickets/{ticket}/start', [\App\Http\Controllers\Api\TicketController::class, 'start']);
    Route::post('/tickets/{ticket}/complete', [\App\Http\Controllers\Api\TicketController::class, 'complete']);
    Route::post('/tickets/{ticket}/notes', [\App\Http\Controllers\Api\TicketController::class, 'addNote']);
    
    // Encounters (Phase 1)
    Route::apiResource('encounters', \App\Http\Controllers\Api\EncounterController::class);
    
    // Payments (Phase 2)
    Route::prefix('payments')->group(function () {
        Route::post('/initiate', [\App\Http\Controllers\Api\PaymentController::class, 'initiate']);
        Route::post('/confirm', [\App\Http\Controllers\Api\PaymentController::class, 'confirm']);
        Route::post('/encounters/{encounter}/confirm-at-hospital', [\App\Http\Controllers\Api\PaymentController::class, 'confirmAtHospital'])
            ->middleware('role:reception|admin');
    });
    
    // Referrals (Phase 4)
    Route::post('/tickets/{ticket}/refer', [\App\Http\Controllers\Api\ReferralController::class, 'refer']);
    Route::get('/referrals', [\App\Http\Controllers\Api\ReferralController::class, 'index']);
    Route::get('/referrals/{referral}', [\App\Http\Controllers\Api\ReferralController::class, 'show']);
    
    // Orders (Phase 5)
    Route::post('/tickets/{ticket}/orders', [\App\Http\Controllers\Api\OrderController::class, 'store']);
    Route::get('/orders', [\App\Http\Controllers\Api\OrderController::class, 'index']);
    Route::get('/orders/{order}', [\App\Http\Controllers\Api\OrderController::class, 'show']);
    Route::put('/orders/{order}', [\App\Http\Controllers\Api\OrderController::class, 'update']);
    Route::get('/encounters/{encounter}/orders', [\App\Http\Controllers\Api\OrderController::class, 'encounterOrders']);
    
    // Staff directory (for ticket creation)
    Route::get('/staff/doctors', [\App\Http\Controllers\Api\UserController::class, 'doctors']);
    Route::get('/staff/patients', [\App\Http\Controllers\Api\UserController::class, 'patients'])->middleware('role:admin|reception');
    
    // Staff IT Chatbot (Phase 6)
    Route::prefix('staff/chatbot')->group(function () {
        Route::post('/chat', [\App\Http\Controllers\Api\StaffChatbotController::class, 'chat']);
        Route::post('/ticket', [\App\Http\Controllers\Api\StaffChatbotController::class, 'createTicket']);
    });

    // Users (Admin only)
    Route::apiResource('users', \App\Http\Controllers\Api\UserController::class)->middleware('role:admin');
    Route::post('/users/{user}/activate', [\App\Http\Controllers\Api\UserController::class, 'activate'])->middleware('role:admin');
    
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
    
    // Chatbot (stateful conversation with strict state machine)
    Route::prefix('chatbot')->group(function () {
        Route::post('/start', [\App\Http\Controllers\Api\ChatbotController::class, 'start']); // New session
        Route::post('/message', [\App\Http\Controllers\Api\ChatbotController::class, 'message']);
        Route::post('/analyze', [\App\Http\Controllers\Api\ChatbotController::class, 'analyze']); // Legacy
        Route::get('/session', [\App\Http\Controllers\Api\ChatbotController::class, 'session']);
        Route::post('/reset', [\App\Http\Controllers\Api\ChatbotController::class, 'reset']);
        Route::post('/select-department', [\App\Http\Controllers\Api\ChatbotController::class, 'selectDepartment']);
        Route::post('/select-doctor', [\App\Http\Controllers\Api\ChatbotController::class, 'selectDoctor']);
        Route::post('/select-time', [\App\Http\Controllers\Api\ChatbotController::class, 'selectTime']);
    });
});
