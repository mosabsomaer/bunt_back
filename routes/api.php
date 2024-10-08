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

// Protected routes (require authentication)
Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::delete('orders/{id}', [OrderController::class, 'destroy'])->name('orders.destroy');

    Route::get('admins', [AdminController::class, 'index'])->name('api.admins.index');
    Route::get('admins/{id}', [AdminController::class, 'show'])->name('api.admins.show');
    Route::put('admins/{id}', [AdminController::class, 'update'])->name('api.admins.update');
    Route::delete('admins/{id}', [AdminController::class, 'destroy'])->name('api.admins.destroy');
    Route::get('statistics', [AdminController::class, 'statistics'])->name('api.admins.statistics');
    Route::post('admins/logout', [AdminController::class, 'logoutadmin'])->name('api.admins.logoutadmin');



    Route::get('files', [FileController::class, 'index'])->name('files.index');
    Route::get('files/{id}', [FileController::class, 'show'])->name('files.show');
    Route::put('files/{id}', [FileController::class, 'update'])->name('files.update');
    Route::delete('files/{id}', [FileController::class, 'destroy'])->name('files.destroy');

    Route::get('machines', [MachineController::class, 'index'])->name('api.machines.index');
    Route::get('machines/{id}', [MachineController::class, 'show'])->name('api.machines.show');
    Route::delete('machines/{id}', [MachineController::class, 'destroy'])->name('api.machines.destroy');
    Route::post('machines', [MachineController::class, 'store'])->name('machines.store');

});

// Public routes (no authentication required)
Route::post('/auth/register', [AdminController::class, 'store'])->name('api.admins.store');
Route::post('auth/login', [AdminController::class, 'loginAdmin'])->name('api.admins.login');
Route::post('files', [FileController::class, 'store'])->name('files.store');
Route::get('createorders', [OrderController::class, 'store'])->name('orders.store');
Route::put('orders/{id}', [OrderController::class, 'update'])->name('orders.update');
Route::put('machines/{id}', [MachineController::class, 'update'])->name('api.machines.update');


//bunt machine routes
Route::middleware(['verify.printer.token'])->group(function () {
    Route::get('/downloadfile/{order_id}', [FileController::class, 'downloadFile'])->name('file.download');
    Route::get('/showbyorder/{order_id}', [FileController::class, 'showByOrder'])->name('files.showByOrder');
});
