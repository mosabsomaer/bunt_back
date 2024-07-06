<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DownloadController;
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
    Route::put('orders/{id}', [OrderController::class, 'update'])->name('orders.update');
    Route::delete('orders/{id}', [OrderController::class, 'destroy'])->name('orders.destroy');



    Route::get('/admins', [AdminController::class, 'index'])->name('api.admins.index');
    Route::get('/admins/{id}', [AdminController::class, 'show'])->name('api.admins.show');
    Route::put('/admins/{id}', [AdminController::class, 'update'])->name('api.admins.update');
    Route::delete('/admins/{id}', [AdminController::class, 'destroy'])->name('api.admins.destroy');
    Route::apiResource('/admins', 'AdminController')->except(['index', 'show', 'update', 'destroy']);


    Route::get('files', [FileController::class, 'index'])->name('files.index');
    Route::get('files/{id}', [FileController::class, 'show'])->name('files.show');
    Route::put('files/{id}', [FileController::class, 'update'])->name('files.update');
    Route::delete('files/{id}', [FileController::class, 'destroy'])->name('files.destroy');
});

// Public routes (no authentication required)
Route::post('/auth/register', [AdminController::class, 'store'])->name('api.admins.store');
Route::post('/auth/login', [AdminController::class, 'loginAdmin'])->name('api.admins.login');
Route::post('files', [FileController::class, 'store'])->name('files.store');
Route::get('createorders', [OrderController::class, 'store'])->name('orders.store');
Route::get('/downloadfile/{order_id}', [DownloadController::class, 'downloadFile'])->name('file.download'); //you need to put API on this one and make only the printer machine able to access it
