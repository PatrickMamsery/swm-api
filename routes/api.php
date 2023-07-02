<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\PassportAuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\MeterController;
use App\Http\Controllers\API\QueryController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\ForgotPasswordController;
use App\Http\Controllers\API\BillController;

use App\Http\Middleware\WithoutLinks;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/register', [PassportAuthController::class, 'register']);
Route::post('/login', [PassportAuthController::class, 'login'])->name('login.api');
Route::post('password-reset', [ForgotPasswordController::class, 'sendResetLinkResponse']);

Route::get('api-test', function() {
    return response()->json(['message' => 'Up and okay!'], 200);
});

// populate meter readings
Route::post('/meter-reading', [MeterController::class, 'storeMeterReadings']);

Route::get('/webhook/meter-readings/{meterNumber?}', [MeterController::class, 'getUpdatedMeterReading']);


Route::middleware('auth:api')->group(function() {

    Route::group(['middleware' => 'withoutlink'], function() {
        Route::apiResource('meters', MeterController::class)->except(['update', 'destroy']);

        Route::get('/meter-readings/{meter?}', [MeterController::class, 'getMeterReadings']);

        // Route::get('/meter-trends', [MeterController::class, 'getMeterTrends']);

        Route::get('/meter-trends', [MeterController::class, 'getMeterTrendsV2']);

        Route::apiResource('payments', PaymentController::class)->except(['update', 'destroy']);

        Route::apiResource('bills', BillController::class)->except(['store', 'update', 'destroy']);

        // Route::get('/payments/{payment?}', [PaymentController::class, 'getPaymentsByMeter']);

        Route::apiResource('queries', QueryController::class);
    });

    // Password manipulation routes
    Route::post('change-password', [UserController::class, 'changePassword']);

    Route::post('/logout', [PassportAuthController::class, 'logout']);

    Route::get('/test', function() {
        return response()->json(['message' => 'Hello World!'], 200);
    });
});
