<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\PassportAuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\MeterController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\ForgotPasswordController;

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

// populate meter readings
Route::post('/meter-reading', [MeterController::class, 'storeMeterReadings']);

Route::middleware('auth:api')->group(function() {

    Route::apiResource('meters', MeterController::class)->middleware('withoutlink');

    Route::get('/meter-readings/{meter?}', [MeterController::class, 'getMeterReadings'])->middleware('withoutlink');

    Route::apiResource('payments', PaymentController::class)->middleware('withoutlink');

    // Password manipulation routes
    Route::post('change-password', [UserController::class, 'changePassword']);

    Route::post('/logout', [PassportAuthController::class, 'logout']);

    Route::get('/test', function() {
        return response()->json(['message' => 'Hello World!'], 200);
    });
});
