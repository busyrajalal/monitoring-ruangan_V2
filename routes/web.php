<?php

use App\Http\Controllers\ControllerDhtmq;
use Illuminate\Support\Facades\Route;

Route::get('/', [ControllerDhtmq::class, 'index'])->name('dashboard');
Route::get('dashboard', [ControllerDhtmq::class, 'index']);
Route::get('sensor', [ControllerDhtmq::class, 'suhu'])->name('suhu');
Route::get('udara', [ControllerDhtmq::class, 'gas'])->name('gas');