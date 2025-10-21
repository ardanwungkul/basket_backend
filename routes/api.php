<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GuardianController;
use App\Http\Controllers\MemberBillController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberFileController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TrainingScheduleController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware(['jwt.auth'])->group(function () {
    Route::resource('bill', MemberBillController::class);
    Route::resource('parent', GuardianController::class);
    Route::resource('member', MemberController::class);
    Route::resource('users', UserController::class);
    Route::resource('payment', PaymentController::class);

    Route::post('member-verification', [MemberFileController::class, 'store']);
    Route::post('confirm-payment', [PaymentController::class, 'confirmPayment']);
    Route::get('me', [AuthController::class, 'getAuthenticatedUser']);

    Route::get('getByAuth/member', [MemberController::class, 'getByAuth']);
    Route::get('getByAuth/parent', [GuardianController::class, 'getByAuth']);
    Route::get('getByAuth/bill', [MemberBillController::class, 'getByAuth']);
    Route::get('getByAuth/payment', [PaymentController::class, 'getByAuth']);


    Route::prefix('training')->group(function () {
        Route::get('schedule', [TrainingScheduleController::class, 'index']);
        Route::post('schedule', [TrainingScheduleController::class, 'store']);
        Route::delete('schedule/{id}', [TrainingScheduleController::class, 'destroy']);
    });

    Route::prefix('attendance')->group(function () {
        Route::get('/', [AttendanceController::class, 'index']);
        Route::post('/', [AttendanceController::class, 'store']);
        Route::post('/scan-qr', [AttendanceController::class, 'scanQR']);
        Route::put('/{id}', [AttendanceController::class, 'update']);
        Route::delete('/{id}', [AttendanceController::class, 'destroy']);
    });

});
