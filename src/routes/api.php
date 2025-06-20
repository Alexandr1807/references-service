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
    Route::post('swift-codes/import', [SwiftCodeController::class, 'import']);
    Route::get('swift-codes/export', [SwiftCodeController::class, 'export']);
    Route::apiResource('swift-codes', SwiftCodeController::class);

    // Бюджетополучатели
    Route::post('budget-holders/import', [BudgetHolderController::class, 'import']);
    Route::get('budget-holders/export', [BudgetHolderController::class, 'export']);
    Route::apiResource('budget-holders', BudgetHolderController::class);

    // Счета казначейства
    Route::post('treasury-accounts/import', [TreasuryAccountController::class, 'import']);
    Route::get('treasury-accounts/export', [TreasuryAccountController::class, 'export']);
    Route::apiResource('treasury-accounts', TreasuryAccountController::class);
});

