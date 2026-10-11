<?php

use App\Http\Controllers\ControllerDhtmq;
use Illuminate\Support\Facades\Route;

//terima data dari esp
Route::get('terima-data/{suhu}/{kelembaban}/{gas}/{co2}/{amonia}/{benzena}/{alkohol}/{asap}', [ControllerDhtmq::class, 'update']);

Route::get('dashboard-realtime', [ControllerDhtmq::class, 'getAllData']);