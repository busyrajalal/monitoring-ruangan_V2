<?php

namespace App\Http\Controllers;

use App\Models\HistoryDhtmq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControllerHistoryDhtmq extends Controller
{
    protected $HistoryDhtmq;

    public function __construct() {
        $this->HistoryDhtmq = new HistoryDhtmq();
    }

    // grafik: rata-rata per jam (24 jam), per 6 jam (7 hari), per hari (30 hari)
    public function getRiwayat(Request $request)
    {
        $aturan = [
            '24' => [24,  "DATE_FORMAT(waktu, '%Y-%m-%d %H:00:00')"],
            '7'  => [168, "CONCAT(DATE(waktu), ' ', LPAD(FLOOR(HOUR(waktu) / 6) * 6, 2, '0'), ':00:00')"],
            '30' => [720, "CONCAT(DATE(waktu), ' 12:00:00')"],
        ];

        $rentang = $request->query('rentang', '24');
        if (!isset($aturan[$rentang])) {
            $rentang = '24';
        }
        [$jam, $kelompok] = $aturan[$rentang];

        $baris = DB::table('history_dhtmq')
            ->selectRaw($kelompok . ' AS slot, AVG(suhu) AS suhu, AVG(kelembaban) AS humi')
            ->where('waktu', '>=', now()->subHours($jam))
            ->groupBy('slot')
            ->orderBy('slot')
            ->get();

        return response()->json([
            'waktu' => $baris->pluck('slot')->values(),
            'suhu'  => $baris->map(fn ($b) => round((float) $b->suhu, 1))->values(),
            'humi'  => $baris->map(fn ($b) => round((float) $b->humi, 1))->values(),
        ]);
    }

    // kartu: rata-rata, suhu max & min hari ini (sejak 00:00)
    public function getRingkasan()
    {
        $hariIni = $this->HistoryDhtmq->where('waktu', '>=', now()->startOfDay());

        $jumlah = (clone $hariIni)->count();
        if ($jumlah === 0) {
            return response()->json(['jumlah' => 0]);
        }

        $rata = (clone $hariIni)->selectRaw('AVG(suhu) AS suhu, AVG(kelembaban) AS humi')->first();
        $max  = (clone $hariIni)->orderByDesc('suhu')->first();
        $min  = (clone $hariIni)->orderBy('suhu')->first();

        return response()->json([
            'jumlah'    => $jumlah,
            'avg_suhu'  => round((float) $rata->suhu, 1),
            'avg_humi'  => round((float) $rata->humi, 0),
            'max_suhu'  => $max->suhu,
            'max_waktu' => $max->waktu->format('H:i'),
            'min_suhu'  => $min->suhu,
            'min_waktu' => $min->waktu->format('H:i'),
        ]);
    }

    // tabel: 10 data terakhir
    public function getLog()
    {
        $data = $this->HistoryDhtmq->orderBy('id', 'desc')->limit(10)->get()->map(fn ($r) => [
            'waktu' => $r->waktu->format('d-m-Y H:i:s'),
            'suhu'  => $r->suhu,
            'humi'  => $r->kelembaban,
        ]);

        return response()->json($data->values());
    }
}