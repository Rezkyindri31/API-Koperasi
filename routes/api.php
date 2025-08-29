<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SavingController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\SettlementController;
use App\Http\Controllers\DividendController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| Public routes (no auth)
|--------------------------------------------------------------------------
*/
Route::post('/login',            [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/register',         [AuthController::class, 'registerKaryawan'])->middleware('throttle:5,1');
Route::post('/admin/register',   [AuthController::class, 'registerAdmin'])->middleware('throttle:5,1');

/*
|--------------------------------------------------------------------------
| Protected routes (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/me',            [AuthController::class, 'me']);
    Route::post('/logout',       [AuthController::class, 'logout']);

   // Simpanan
    Route::get('/savings',             [SavingController::class, 'index'])->name('savings.index');
    Route::post('/savings',            [SavingController::class, 'store'])->name('savings.store');    
    Route::put('/savings/{saving}',    [SavingController::class, 'update'])->name('savings.update');   
    Route::delete('/savings/{saving}', [SavingController::class, 'destroy'])->name('savings.destroy');
    Route::get('/savings/summary', [SavingController::class, 'summary']); 
    

    // Pinjaman
    Route::get('/loans',                           [LoanController::class, 'index'])->name('loans.index');
    Route::get('/loans/{loan}',                    [LoanController::class, 'show'])->name('loans.show');      
    Route::post('/loans',                          [LoanController::class, 'store'])->name('loans.store');
    Route::patch('/loans/{loan}',                  [LoanController::class, 'update'])->name('loans.update');  
    Route::post('/loans/{loan}/approve',           [LoanController::class, 'approve'])->name('loans.approve');
    Route::post('/loans/{loan}/reject',            [LoanController::class, 'reject'])->name('loans.reject');
    Route::post('/loans/{loan}/cancel',            [LoanController::class, 'cancel'])->name('loans.cancel');

    // Pelunasan 
    Route::get('/settlements',                               [SettlementController::class, 'index'])->name('settlements.index');
    Route::post('/settlements',                              [SettlementController::class, 'store'])->name('settlements.store');     // karyawan
    Route::post('/settlements/{settlement}/approve',         [SettlementController::class, 'approve'])->name('settlements.approve'); // admin
    Route::post('/settlements/{settlement}/reject',          [SettlementController::class, 'reject'])->name('settlements.reject');   // admin
    
    Route::get('/dividend', [DividendController::class, 'show']);

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/summary', [UserController::class, 'summary']);
    Route::get('/settlements/{settlement}/proof', [SettlementController::class, 'proof'])
        ->name('settlements.proof');
});