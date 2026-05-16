<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ReminderController;
use App\Http\Controllers\Api\V1\ShopController;
use App\Http\Controllers\Api\V1\StatementController;
use App\Http\Controllers\Api\V1\SyncController;
use App\Http\Controllers\Api\V1\TransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('otp/request', [AuthController::class, 'requestOtp']);
        Route::post('otp/verify', [AuthController::class, 'verifyOtp']);
        Route::post('token/refresh', [AuthController::class, 'refresh']);
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/logout-all', [AuthController::class, 'logoutAll']);

        Route::get('profile', [ProfileController::class, 'show']);
        Route::patch('profile', [ProfileController::class, 'update']);
        Route::delete('profile', [ProfileController::class, 'destroy']);

        Route::get('shop', [ShopController::class, 'show']);
        Route::post('shop', [ShopController::class, 'store']);

        Route::middleware(['shop.resolve', 'shop.access'])->group(function (): void {
            Route::patch('shop', [ShopController::class, 'update']);
            Route::delete('shop', [ShopController::class, 'destroy']);

            Route::get('customers', [CustomerController::class, 'index']);
            Route::post('customers', [CustomerController::class, 'store']);
            Route::post('customers/bulk', [CustomerController::class, 'bulkStore']);
            Route::get('customers/{uuid}', [CustomerController::class, 'show']);
            Route::patch('customers/{uuid}', [CustomerController::class, 'update']);
            Route::delete('customers/{uuid}', [CustomerController::class, 'destroy']);
            Route::post('customers/{uuid}/statement', [StatementController::class, 'generate']);

            Route::get('transactions', [TransactionController::class, 'index']);
            Route::post('transactions', [TransactionController::class, 'store']);
            Route::post('transactions/adjustment', [TransactionController::class, 'adjustment']);
            Route::get('transactions/{uuid}', [TransactionController::class, 'show']);
            Route::post('transactions/{uuid}/correction', [TransactionController::class, 'correct']);
            Route::post('transactions/{uuid}/reverse', [TransactionController::class, 'reverse']);
            Route::patch('transactions/{uuid}/metadata', [TransactionController::class, 'updateMetadata']);

            Route::get('payments', [PaymentController::class, 'index']);
            Route::post('payments', [PaymentController::class, 'store']);
            Route::get('payments/{uuid}', [PaymentController::class, 'show']);
            Route::post('payments/{uuid}/correction', [PaymentController::class, 'correct']);
            Route::post('payments/{uuid}/reverse', [PaymentController::class, 'reverse']);
            Route::patch('payments/{uuid}/metadata', [PaymentController::class, 'updateMetadata']);

            Route::post('sync/push', [SyncController::class, 'push']);
            Route::get('sync/pull', [SyncController::class, 'pull']);

            Route::get('reminders', [ReminderController::class, 'index']);
            Route::post('reminders/send', [ReminderController::class, 'send']);

            Route::get('statements/{uuid}/download', [StatementController::class, 'download']);
        });
    });
});
