<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\PotController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/user', UserController::class)->middleware(['auth:sanctum', 'ability:read-user']);

Route::apiResource('transactions', TransactionController::class)->middleware(['auth:sanctum', 'ability:manage-transactions']);

Route::apiResource('budgets', BudgetController::class)->middleware(['auth:sanctum', 'ability:manage-budgets']);

Route::apiResource('pots', PotController::class)->middleware(['auth:sanctum', 'ability:manage-pots']);
