<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\PotController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/user', UserController::class)->middleware(['auth:sanctum', 'ability:read-user']);

// Transactions
Route::apiResource('transactions', TransactionController::class)->middleware(['auth:sanctum', 'ability:manage-transactions']);

// Budgets
Route::middleware(['auth:sanctum', 'ability:manage-budgets'])
    ->controller(BudgetController::class)
    ->prefix('budgets')
    ->name('budgets.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{budget}', 'show')->whereUuid('budget')->name('show');
        Route::match(['put', 'patch'], '/{budget}', 'update')->whereUuid('budget')->name('update');
        Route::delete('/{budget}', 'destroy')->whereUuid('budget')->name('destroy');
    });

// Pots
Route::middleware(['auth:sanctum', 'ability:manage-pots'])
    ->controller(PotController::class)
    ->prefix('pots')
    ->name('pots.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{pot}', 'show')->whereUuid('pot')->name('show');
        Route::match(['put', 'patch'], '/{pot}', 'update')->whereUuid('pot')->name('update');
        Route::delete('/{pot}', 'destroy')->whereUuid('pot')->name('destroy');
        Route::post( '/{pot}/deposit', 'deposit')->whereUuid('pot')->name('deposit');
        Route::post('/{pot}/withdraw', 'withdraw')->whereUuid('pot')->name('withdraw');
    });
