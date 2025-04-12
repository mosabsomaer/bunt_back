<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MachineController;
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

// Public routes (no authentication required)
Route::post('auth/login', [AdminController::class, 'loginAdmin'])->name('api.admins.login');
Route::post('auth/register', [AdminController::class, 'store'])->name('api.admins.store');
Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
Route::post('files', [FileController::class, 'store'])->name('files.store');
Route::put('orders/{id}', [OrderController::class, 'update'])->name('orders.update');
Route::patch('orders/{id}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
Route::put('machines/{id}', [MachineController::class, 'update'])->name('api.machines.update');

// Protected routes (require authentication)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('admin')->group(function () {
        Route::apiResource('admins', AdminController::class, [
            'as' => 'api.admins',
            'except' => ['store']
        ]);
        Route::post('logout', [AdminController::class, 'logoutadmin'])->name('api.admins.logout');
        Route::get('statistics', [AdminController::class, 'statistics'])->name('api.admins.statistics');
    });
    Route::apiResource('orders', OrderController::class)->except(['store', 'update']);
    Route::apiResource('files', FileController::class)->except(['store']);
    Route::apiResource('machines', MachineController::class)->except(['update']);
});

// Printer routes
Route::middleware(['verify.printer.token'])->prefix('printer')->group(function () {
    Route::get('files/{order_id}/download', [FileController::class, 'downloadFile'])->name('file.download');
    Route::get('files/order/{order_id}', [FileController::class, 'showByOrder'])->name('files.showByOrder');
});
