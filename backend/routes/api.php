<?php

use App\Http\Controllers\Api\TicketAssignmentController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TicketStatusController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TicketCommentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/tickets', [TicketController::class, 'index']);
    Route::post('/tickets', [TicketController::class, 'store']);
    Route::get('/tickets/{ticket}', [TicketController::class, 'show']);
    Route::patch('/tickets/{ticket}', [TicketController::class, 'update']);
    Route::patch('/tickets/{ticket}/status', TicketStatusController::class);
    Route::patch('/tickets/{ticket}/assign', TicketAssignmentController::class);
    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::patch('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    Route::get('/tickets/{ticket}/comments', [TicketCommentController::class, 'index']);
    Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store']);
});