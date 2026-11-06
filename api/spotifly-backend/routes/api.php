<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ActionController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\InsectDataController;
use App\Http\Controllers\Api\KPIController;
use App\Http\Controllers\Api\ParcelController;
use App\Http\Controllers\Api\SensorController;
use App\Http\Controllers\Api\WeatherController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PestPredictionController;

Route::withoutMiddleware([\App\Http\Middleware\VerifyOrigin::class])
    ->group(function () {
        Route::get('/health', function () {
            return response()->json([
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'service' => 'spotifly-api',
                'version' => config('app.version', '1.0.0')
            ]);
        });
    });

// All API Routes under /api prefix
Route::prefix('api')->group(function () {
    // Public auth routes
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->name('api.auth.register');
        Route::post('login', [AuthController::class, 'login'])->name('api.auth.login');
        Route::post('refresh', [AuthController::class, 'refresh'])->name('api.auth.refresh');
    });

    // Protected routes (require Gatekeeper JWT token in Authorization header)
    Route::middleware('auth.gatekeeper')->group(function () {
        // Auth
        Route::prefix('auth')->group(function () {
            Route::get('user', [AuthController::class, 'profile'])->name('api.auth.user');
            Route::post('logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        });

        // Parcels
        Route::get('parcels', [ParcelController::class, 'index']);
        Route::get('parcels/{id}', [ParcelController::class, 'show']);
        Route::post('parcels', [ParcelController::class, 'store']);
        Route::put('parcels/{id}', [ParcelController::class, 'update']);
        Route::delete('parcels/{id}', [ParcelController::class, 'destroy']);

        // Sensors (Agricultural Machines)
        Route::get('sensors', [SensorController::class, 'index']);
        Route::get('parcels/{parcelId}/sensors', [SensorController::class, 'getSensorsForParcel']);

        // Actions
        Route::get('actions', [ActionController::class, 'index']);
        Route::get('actions/{id}', [ActionController::class, 'show']);
        Route::post('actions', [ActionController::class, 'store']);
        Route::put('actions/{id}', [ActionController::class, 'update']);
        Route::delete('actions/{id}', [ActionController::class, 'destroy']);
        Route::post('actions/{id}/complete', [ActionController::class, 'complete']);

        // Alerts
        Route::get('alerts', [AlertController::class, 'index']);
        Route::get('alerts/timeline', [AlertController::class, 'timeline']);
        Route::post('alerts/{id}/acknowledge', [AlertController::class, 'acknowledge']);

        // Weather
        Route::get('weather/hourly', [WeatherController::class, 'forecast']);
        Route::get('weather/daily', [WeatherController::class, 'dailyForecast']);

        // Insect/Pest Data
        Route::get('parcels/{id}/insects', [InsectDataController::class, 'index']);

        // KPIs
        Route::get('parcels/{id}/kpis', [KPIController::class, 'show']);

        Route::get('predictions/pest/latest', [PestPredictionController::class, 'latest']);
        Route::get('predictions/pest', [PestPredictionController::class, 'index']);
        Route::get('parcels/{parcelId}/predictions/pest', [PestPredictionController::class, 'forParcel']);
    });
});
