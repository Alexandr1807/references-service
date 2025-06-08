<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\API\SwiftCodeController;
use App\Http\Controllers\API\TreasuryAccountController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\BudgetHolderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::post('register', [AuthController::class, 'register']);
Route::post('login',    [AuthController::class, 'login']);
Route::middleware('auth:api')->post('logout', [AuthController::class, 'logout']);
Route::middleware('auth:api')->get('users', [UserController::class, 'index']);


Route::middleware('auth:api')->group(function () {
    // SWIFT
    Route::apiResource('swift-codes', SwiftCodeController::class);
    Route::post('swift-codes/import', [SwiftCodeController::class, 'import']);

    // Бюджетополучатели
    Route::apiResource('budget-holders', BudgetHolderController::class);
    Route::post('budget-holders/import', [BudgetHolderController::class, 'import']);

    // Счета казначейства
    Route::apiResource('treasury-accounts', TreasuryAccountController::class);
    Route::post('treasury-accounts/import', [TreasuryAccountController::class, 'import']);
});

