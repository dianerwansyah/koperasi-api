<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SavingController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\SettlementController;

// Login public
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    // Data user login
    Route::middleware('role:admin,karyawan')->get('/user', function (Request $request) {
        return $request->user();
    });
    Route::middleware('role:admin')->get('/user/getuser', [UserController::class, 'index']);

    // Simpanan
    Route::middleware('role:admin,karyawan')->get('/savings', [SavingController::class, 'index']);
    Route::middleware('role:admin')->post('/savings', [SavingController::class, 'store']);
    Route::middleware('role:admin')->put('/savings/{id}', [SavingController::class, 'update']);
    Route::middleware('role:admin')->delete('/savings/{id}', [SavingController::class, 'destroy']);
    Route::middleware('role:admin')->get('/savings/calculate', [SavingController::class, 'calculate']);

    // Pinjaman
    Route::middleware('role:admin,karyawan')->get('/loans', [LoanController::class, 'index']);
    Route::middleware('role:admin,karyawan')->post('/loans', [LoanController::class, 'store']);
    Route::middleware('role:admin')->post('/loans/{id}', [LoanController::class, 'update']);

    // Pelunasan
    Route::middleware('role:admin')->get('/settlement', [SettlementController::class, 'index']);
    Route::middleware('role:admin,karyawan')->post('/settlement', [SettlementController::class, 'store']);
    Route::middleware('role:admin')->post('/settlement/{id}', [SettlementController::class, 'update']);

    // Logout
    Route::middleware('role:admin,karyawan')->post('/logout', [AuthController::class, 'logout']);
});
