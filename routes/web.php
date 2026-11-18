<?php

use App\Http\Controllers\ControllerDhtmq;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/register', [AuthController::class, 'registerLihat'])->name('register.lihat');
Route::post('/register/submit', [AuthController::class, 'registerSubmit'])->name('register.submit');

Route::get('/login', [AuthController::class, 'loginLihat'])->name('login.lihat');
Route::post('/login/submit', [AuthController::class, 'loginSubmit'])->name('login.submit');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [ControllerDhtmq::class, 'index'])->name('dashboard');
    Route::get('dashboard', [ControllerDhtmq::class, 'index']);
    Route::get('sensor', [ControllerDhtmq::class, 'suhu'])->name('suhu');
    Route::get('udara', [ControllerDhtmq::class, 'gas'])->name('gas');
});