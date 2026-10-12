<?php

use App\Http\Controllers\ControllerDhtmq;
use App\Http\Controllers\ControllerHistoryDhtmq;
use Illuminate\Support\Facades\Route;

//terima data dari esp
Route::get('terima-data/{suhu}/{kelembaban}/{gas}/{co2}/{amonia}/{benzena}/{alkohol}/{asap}', [ControllerDhtmq::class, 'update']);

Route::get('dashboard-realtime', [ControllerDhtmq::class, 'getAllData']);

// history
Route::get('history-dhtmq/riwayat', [ControllerHistoryDhtmq::class, 'getRiwayat']);
Route::get('history-dhtmq/ringkasan', [ControllerHistoryDhtmq::class, 'getRingkasan']);
Route::get('history-dhtmq/log', [ControllerHistoryDhtmq::class, 'getLog']);