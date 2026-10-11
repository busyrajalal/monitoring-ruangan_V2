<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorDhtmq extends Model
{
    protected $table = 'sensor_dhtmq';

    public const CREATED_AT = 'waktu';
    public const UPDATED_AT = null; 

    protected $fillable = [
        'suhu', 'kelembaban', 'gas', 'co2', 'amonia', 'benzena', 'alkohol', 'asap',
    ];

    protected $casts = [
        'suhu' => 'float', 
        'kelembaban' => 'float', 
        'gas' => 'float',
        'co2' => 'float', 
        'amonia' => 'float', 
        'benzena' => 'float',
        'alkohol' => 'float', 
        'asap' => 'float', 
        'waktu' => 'datetime',
    ];
}
