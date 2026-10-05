<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'dashboard')->name('dashboard');
Route::view('/suhu-kelembaban', 'suhu-kelembaban')->name('suhu');
Route::view('/gas', 'gas')->name('gas');
