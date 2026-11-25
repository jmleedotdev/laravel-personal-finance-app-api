<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\PotController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', UserController::class);
    Route::apiResource('transactions', TransactionController::class);
    Route::apiResource('budgets', BudgetController::class);
    Route::apiResource('pots', PotController::class);
});
