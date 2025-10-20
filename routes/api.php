<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GuardianController;
use App\Http\Controllers\MemberBillController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MemberFileController;
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
    Route::resource('parent', GuardianController::class);
    Route::resource('member', MemberController::class);
    Route::resource('users', UserController::class);

    Route::post('member-verification', [MemberFileController::class, 'store']);
    Route::get('me', [AuthController::class, 'getAuthenticatedUser']);

    Route::get('getByAuth/member', [MemberController::class, 'getByAuth']);
    Route::get('getByAuth/parent', [GuardianController::class, 'getByAuth']);
    Route::get('getByAuth/bill', [MemberBillController::class, 'getByAuth']);
});
