<?php

namespace App\Http\Controllers;

use App\Models\SensorDhtmq;
use Illuminate\Support\Facades\DB;

class ControllerDhtmq extends Controller
{
    protected $db;
    protected $builder;
    protected $SensorDhtmq;

    public function __construct() {
        $this->db = DB::connection();
        $this->builder = DB::table('sensor_dhtmq');

        $this->SensorDhtmq = new SensorDhtmq();
    }

    public function index()
    {
        $data = [
            'title' => 'Dashboard Monitoring Ruangan',
            'dataSensor' => $this->SensorDhtmq->where('id', 1)->get()
        ];

        return view('dashboard', $data);
    }

    public function suhu()
    {
        $data = [
            'title' => 'Sensor Suhu & Kelembaban',
            'dataSensor' => $this->SensorDhtmq->where('id', 1)->get()
        ];

        return view('suhu-kelembaban', $data);
    }

    public function gas()
    {
        $data = [
            'title' => 'Sensor Kualitas Udara',
            'dataSensor' => $this->SensorDhtmq->find(1)
        ];

        return view('gas', $data);
    }

    public function getAllData()
    {
        $data = $this->SensorDhtmq->find(1);
        return response()->json($data);
    }

    public function update($suhu, $kelembaban, $gas, $co2, $amonia, $benzena, $alkohol, $asap)
    {
        $updateData = [
            'suhu'       => $suhu,
            'kelembaban' => $kelembaban,
            'gas'        => $gas,
            'co2'        => $co2,
            'amonia'     => $amonia,
            'benzena'    => $benzena,
            'alkohol'    => $alkohol,
            'asap'       => $asap,
            'waktu'      => now(),
        ];

        $this->builder->updateOrInsert(['id' => 1], $updateData);

        return 'OK';
    }
}