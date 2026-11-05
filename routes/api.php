<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CommodityController;
use App\Http\Controllers\Api\V1\EmailVerificationController;
use App\Http\Controllers\Api\V1\MarketController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\Api\V1\PriceReportController;
use App\Http\Controllers\Api\V1\PriceReportReviewController;
use App\Http\Controllers\Api\V1\SrpController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::middleware('throttle:auth')->group(function () {
            Route::post('register', [AuthController::class, 'register']);
            Route::post('login', [AuthController::class, 'login']);
            Route::post('forgot-password', [PasswordResetController::class, 'forgot']);
            Route::post('reset-password', [PasswordResetController::class, 'reset']);
        });

        Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('email/verification-notification', [EmailVerificationController::class, 'resend'])
                ->middleware('throttle:6,1');
        });
    });

    Route::get('commodities', [CommodityController::class, 'index']);
    Route::get('commodities/{commodity}', [CommodityController::class, 'show']);
    Route::get('commodities/{commodity}/trend', [CommodityController::class, 'trend']);
    Route::get('commodities/{commodity}/compare', [CommodityController::class, 'compare']);

    Route::get('markets', [MarketController::class, 'index']);
    Route::get('markets/{market}', [MarketController::class, 'show']);

    Route::get('srps', [SrpController::class, 'index']);

    Route::get('price-reports', [PriceReportController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('price-reports', [PriceReportController::class, 'store'])
            ->middleware(['verified', 'throttle:price-reports']);

        Route::middleware('can:admin')->group(function () {
            Route::post('commodities', [CommodityController::class, 'store']);
            Route::put('commodities/{commodity}', [CommodityController::class, 'update']);
            Route::delete('commodities/{commodity}', [CommodityController::class, 'destroy']);

            Route::post('markets', [MarketController::class, 'store']);
            Route::put('markets/{market}', [MarketController::class, 'update']);
            Route::delete('markets/{market}', [MarketController::class, 'destroy']);

            Route::post('srps', [SrpController::class, 'store']);
            Route::post('srps/import', [SrpController::class, 'import']);
            Route::delete('srps/{srp}', [SrpController::class, 'destroy']);

            Route::post('price-reports/{priceReport}/hide', [PriceReportReviewController::class, 'hide']);
            Route::delete('price-reports/{priceReport}/hide', [PriceReportReviewController::class, 'unhide']);
        });
    });
});
