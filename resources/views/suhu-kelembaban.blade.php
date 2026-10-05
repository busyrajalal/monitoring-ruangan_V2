@extends('layouts.app')
@section('title', 'Suhu & Kelembaban')
@section('page-title', 'Histori Data & Analisis Suhu & Kelembaban [DHT11]')
@section('breadcrumb', 'Suhu & Kelembaban')

@section('content')
      {{-- ============ 1. STAT CARDS ANALITIK ============ --}}
      <div class="row">
        <div class="col-sm-12 col-md-4">
          <div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center">
            <div class="col-icon"><div class="icon-big text-center icon-success bubble-shadow-small" id="icoNyaman"><i class="fas fa-smile"></i></div></div>
            <div class="col col-stats ms-3 ms-sm-0"><div class="numbers">
              <p class="card-category">Indeks Kenyamanan Ruangan</p>
              <h4 class="card-title"><span id="idxNyaman">-</span> <span class="badge badge-success align-middle" id="badgeNyaman">-</span></h4>
              <small class="text-muted">Thom's Discomfort Index (real-time)</small>
            </div></div>
          </div></div></div>
        </div>
        <div class="col-sm-6 col-md-4">
          <div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center">
            <div class="col-icon"><div class="icon-big text-center icon-primary bubble-shadow-small"><i class="fas fa-calculator"></i></div></div>
            <div class="col col-stats ms-3 ms-sm-0"><div class="numbers">
              <p class="card-category">Rata-Rata Hari Ini</p>
              <h4 class="card-title" id="avgSuhu">-</h4>
              <small class="text-muted" id="avgHumi">-</small>
            </div></div>
          </div></div></div>
        </div>
        <div class="col-sm-6 col-md-4">
          <div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center">
            <div class="col-icon"><div class="icon-big text-center icon-info bubble-shadow-small"><i class="fas fa-arrows-alt-v"></i></div></div>
            <div class="col col-stats ms-3 ms-sm-0"><div class="numbers">
              <p class="card-category">Suhu Max / Min Hari Ini</p>
              <h4 class="card-title" id="maxMin">-</h4>
              <small class="text-muted" id="maxMinWaktu">-</small>
            </div></div>
          </div></div></div>
        </div>
      </div>

      {{-- ============ 2. GRAFIK HISTORI ============ --}}
      <div class="row">
        <div class="col-md-12">
          <div class="card card-round">
            <div class="card-header">
              <div class="card-head-row">
                <div>
                  <div class="card-title"><i class="fas fa-chart-line me-2 text-primary"></i>Tren Suhu &amp; Kelembaban</div>
                  <div class="card-category" id="infoRentang">-</div>
                </div>
                <div class="card-tools">
                  <div class="dropdown" id="filterRentang">
                    <button class="btn btn-label-info btn-round btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                      <i class="fas fa-calendar-alt me-1"></i> <span id="labelRentang">24 Jam Terakhir</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                      <a class="dropdown-item active" href="#" data-range="24">24 Jam Terakhir</a>
                      <a class="dropdown-item" href="#" data-range="7">7 Hari Terakhir</a>
                      <a class="dropdown-item" href="#" data-range="30">30 Hari Terakhir</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div class="chart-container" style="min-height: 350px"><canvas id="historiChart"></canvas></div>
            </div>
          </div>
        </div>
      </div>

      {{-- ============ 3. TABEL LOG & EXPORT ============ --}}
      <div class="row">
        <div class="col-md-12">
          <div class="card card-round">
            <div class="card-header">
              <div class="card-head-row">
                <div>
                  <div class="card-title"><i class="fas fa-history me-2 text-primary"></i>Log Data Terakhir</div>
                  <div class="card-category">10 pembacaan sensor terbaru (diperbarui tiap 5 detik)</div>
                </div>
                <div class="card-tools">
                  <button type="button" class="btn btn-label-success btn-round btn-sm me-2" id="btnExcel">
                    <i class="fas fa-file-excel me-1"></i> Export Excel
                  </button>
                  <button type="button" class="btn btn-label-info btn-round btn-sm" id="btnCsv">
                    <i class="fas fa-download me-1"></i> Export CSV
                  </button>
                </div>
              </div>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead>
                    <tr>
                      <th scope="col" class="ps-4" style="width:70px">No</th>
                      <th scope="col">Timestamp</th>
                      <th scope="col">Suhu</th>
                      <th scope="col">Kelembaban</th>
                      <th scope="col">Status</th>
                    </tr>
                  </thead>
                  <tbody id="logBody"></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
@endsection

@push('scripts')
<script>
  // ====================== KONFIGURASI ======================
  var CSV_DESIMAL = ",";   // pakai "," untuk Excel Indonesia, "." untuk Excel bahasa Inggris
  var CSV_PEMISAH = ";";   // pakai ";" untuk Excel Indonesia, "," untuk Excel bahasa Inggris

  // Batas status: 0 = Normal, 1 = Waspada, 2 = Bahaya (ubah sesuai kebutuhan)
  function levelSuhu(t) { return (t >= 35 || t < 18) ? 2 : ((t >= 32 || t < 20) ? 1 : 0); }
  function levelHumi(h) { return (h > 80 || h < 30) ? 2 : ((h > 70 || h < 40) ? 1 : 0); }
  var NAMA_LEVEL  = ["Normal", "Waspada", "Bahaya"];
  var WARNA_LEVEL = ["success", "warning", "danger"];

  // ====================== UTIL ======================
  function pad(n) { return (n < 10 ? "0" : "") + n; }
  var HARI  = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];
  var BULAN = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
  function fmtWaktu(d) {
    return pad(d.getDate()) + "-" + pad(d.getMonth() + 1) + "-" + d.getFullYear() + " " +
           pad(d.getHours()) + ":" + pad(d.getMinutes()) + ":" + pad(d.getSeconds());
  }
  function rata(arr) { return arr.reduce(function (a, b) { return a + b; }, 0) / arr.length; }

  // Indeks kenyamanan (Thom's Discomfort Index)
  function indeksNyaman(t, h) { return t - 0.55 * (1 - 0.01 * h) * (t - 14.5); }
  function kategoriNyaman(di) {
    if (di < 21) return { teks: "Nyaman",        warna: "success", ikon: "fa-smile" };
    if (di < 24) return { teks: "Cukup Nyaman",  warna: "success", ikon: "fa-smile" };
    if (di < 27) return { teks: "Kurang Nyaman", warna: "warning", ikon: "fa-meh" };
    return              { teks: "Tidak Nyaman",  warna: "danger",  ikon: "fa-frown" };
  }

  // ====================== DATA RIWAYAT (DUMMY) ======================
  // Nanti ganti fungsi bangunRiwayat() dengan fetch() ke API Laravel,
  // format yang dikembalikan: { label: [], suhu: [], humi: [], waktu: [Date] }
  var RENTANG = {
    "24": { nama: "24 Jam Terakhir",  n: 24, stepJam: 1  },
    "7":  { nama: "7 Hari Terakhir",  n: 28, stepJam: 6  },
    "30": { nama: "30 Hari Terakhir", n: 30, stepJam: 24 },
  };
  function seeded(seed) {           // pseudo-random supaya grafik stabil saat filter diganti
    return function () {
      seed |= 0; seed = (seed + 0x6D2B79F5) | 0;
      var t = Math.imul(seed ^ (seed >>> 15), 1 | seed);
      t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
      return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
  }
  function bangunRiwayat(key) {
    var r = RENTANG[key], rnd = seeded(parseInt(key, 10) * 1000 + 7);
    var now = new Date(); now.setMinutes(0, 0, 0);
    var out = { label: [], suhu: [], humi: [], waktu: [] };
    for (var i = r.n - 1; i >= 0; i--) {
      var t = new Date(now.getTime() - i * r.stepJam * 3600000);
      var jam = r.stepJam >= 24 ? 12 : t.getHours();
      var siklus = Math.sin((jam - 9) / 24 * 2 * Math.PI);          // puncak sekitar jam 15:00
      var tren = r.stepJam >= 24 ? 1.2 * Math.sin(i / 4) : 0.6 * Math.sin(i / 9);
      out.suhu.push(+(29.5 + 2.5 * siklus + tren + (rnd() - 0.5) * 0.8).toFixed(1));
      out.humi.push(Math.round(64 - 8 * siklus - tren * 2 + (rnd() - 0.5) * 3));
      out.waktu.push(t);
      out.label.push(r.stepJam >= 24 ? t.getDate() + " " + BULAN[t.getMonth()]
                   : r.stepJam >= 6  ? HARI[t.getDay()] + " " + pad(t.getHours()) + ":00"
                   :                   pad(t.getHours()) + ":00");
    }
    return out;
  }

  // ====================== 1. STAT CARDS ======================
  // "Hari ini" dihitung dari data riwayat 24 jam.
  (function isiStatHariIni() {
    var h = bangunRiwayat("24");
    var iMax = h.suhu.indexOf(Math.max.apply(null, h.suhu));
    var iMin = h.suhu.indexOf(Math.min.apply(null, h.suhu));
    document.getElementById("avgSuhu").textContent = rata(h.suhu).toFixed(1) + " °C";
    document.getElementById("avgHumi").textContent = "Kelembaban rata-rata " + Math.round(rata(h.humi)) + " %";
    document.getElementById("maxMin").textContent = h.suhu[iMax] + "° / " + h.suhu[iMin] + "°";
    document.getElementById("maxMinWaktu").textContent = "Max " + h.label[iMax] + " · Min " + h.label[iMin];
  })();

  function updateNyaman(t, h) {
    var di = indeksNyaman(t, h), k = kategoriNyaman(di);
    document.getElementById("idxNyaman").textContent = di.toFixed(1);
    var b = document.getElementById("badgeNyaman");
    b.textContent = k.teks;
    b.className = "badge badge-" + k.warna + " align-middle";
    var ico = document.getElementById("icoNyaman");
    ico.className = "icon-big text-center bubble-shadow-small icon-" + k.warna;
    ico.innerHTML = '<i class="fas ' + k.ikon + '"></i>';
  }

  // ====================== 2. LINE CHART HISTORI ======================
  var data0 = bangunRiwayat("24");
  var historiChart = new Chart(document.getElementById("historiChart").getContext("2d"), {
    type: "line",
    data: {
      labels: data0.label,
      datasets: [
        { label: "Suhu (°C)", yAxisID: "y-suhu", borderColor: "#fdaf4b", backgroundColor: "rgba(253,175,75,0.15)",
          pointBackgroundColor: "#fdaf4b", pointRadius: 3, borderWidth: 2, lineTension: 0.3, fill: false, data: data0.suhu },
        { label: "Kelembaban (%)", yAxisID: "y-humi", borderColor: "#177dff", backgroundColor: "rgba(23,125,255,0.15)",
          pointBackgroundColor: "#177dff", pointRadius: 3, borderWidth: 2, lineTension: 0.3, fill: false, data: data0.humi },
      ],
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      legend: { position: "top" },
      tooltips: { mode: "index", intersect: false },
      hover: { mode: "index", intersect: false },
      scales: {
        yAxes: [
          { id: "y-suhu", position: "left",  scaleLabel: { display: true, labelString: "Suhu (°C)" },
            ticks: { suggestedMin: 22, suggestedMax: 36 } },
          { id: "y-humi", position: "right", scaleLabel: { display: true, labelString: "Kelembaban (%)" },
            ticks: { suggestedMin: 40, suggestedMax: 90 }, gridLines: { drawOnChartArea: false } },
        ],
        xAxes: [{ gridLines: { display: false }, ticks: { maxTicksLimit: 12, maxRotation: 0 } }],
      },
    },
  });

  function setRentang(key) {
    var d = bangunRiwayat(key);
    historiChart.data.labels = d.label;
    historiChart.data.datasets[0].data = d.suhu;
    historiChart.data.datasets[1].data = d.humi;
    historiChart.update();
    document.getElementById("labelRentang").textContent = RENTANG[key].nama;
    document.getElementById("infoRentang").textContent =
      RENTANG[key].nama + " · " + d.suhu.length + " titik data · rata-rata " +
      rata(d.suhu).toFixed(1) + " °C / " + Math.round(rata(d.humi)) + " %";
    document.querySelectorAll("#filterRentang .dropdown-item").forEach(function (a) {
      a.classList.toggle("active", a.getAttribute("data-range") === key);
    });
  }
  document.querySelectorAll("#filterRentang .dropdown-item").forEach(function (a) {
    a.addEventListener("click", function (e) { e.preventDefault(); setRentang(a.getAttribute("data-range")); });
  });
  setRentang("24");

  // ====================== 3. TABEL LOG & EXPORT ======================
  var logRows = [];     // terbaru di indeks 0, maksimal 10 baris

  function tambahLog(waktu, d) {
    var lv = Math.max(levelSuhu(d.suhu), levelHumi(d.humi));
    logRows.unshift({ waktu: waktu, suhu: d.suhu, humi: d.humi, status: NAMA_LEVEL[lv], warna: WARNA_LEVEL[lv] });
    if (logRows.length > 10) logRows.pop();
  }
  function renderLog() {
    var html = "";
    logRows.forEach(function (r, i) {
      html += "<tr>" +
        '<td class="ps-4">' + (i + 1) + "</td>" +
        "<td>" + fmtWaktu(r.waktu) + "</td>" +
        "<td>" + r.suhu.toFixed(1) + " °C</td>" +
        "<td>" + r.humi + " %</td>" +
        '<td><span class="badge badge-' + r.warna + '">' + r.status + "</span></td>" +
        "</tr>";
    });
    document.getElementById("logBody").innerHTML = html;
  }

  // isi awal: 10 pembacaan mundur tiap 5 detik
  (function () {
    var awal = [];
    for (var i = 9; i >= 0; i--) awal.push({ w: new Date(Date.now() - i * 5000), d: ambilData() });
    awal.forEach(function (x) { tambahLog(x.w, x.d); });
    renderLog();
    var terbaru = awal[awal.length - 1].d;
    updateNyaman(terbaru.suhu, terbaru.humi);
  })();

  // pembacaan baru tiap 5 detik
  setInterval(function () {
    var d = ambilData();
    tambahLog(new Date(), d);
    renderLog();
    updateNyaman(d.suhu, d.humi);
  }, 5000);

  // ---- export ----
  function angka(n, desimal) { return String(n.toFixed(desimal)).replace(".", CSV_DESIMAL); }
  function unduh(isi, nama, tipe) {
    var blob = new Blob(["\uFEFF" + isi], { type: tipe + ";charset=utf-8;" });
    var url = URL.createObjectURL(blob), a = document.createElement("a");
    a.href = url; a.download = nama;
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }
  function stamp() { var n = new Date(); return n.getFullYear() + pad(n.getMonth() + 1) + pad(n.getDate()) + "_" + pad(n.getHours()) + pad(n.getMinutes()); }

  document.getElementById("btnCsv").addEventListener("click", function () {
    var baris = ["No", "Timestamp", "Suhu (C)", "Kelembaban (%)", "Status"].join(CSV_PEMISAH);
    var isi = [baris];
    logRows.forEach(function (r, i) {
      isi.push([i + 1, fmtWaktu(r.waktu), angka(r.suhu, 1), angka(r.humi, 0), r.status].join(CSV_PEMISAH));
    });
    unduh(isi.join("\r\n"), "log-suhu-kelembaban_" + stamp() + ".csv", "text/csv");
  });

  document.getElementById("btnExcel").addEventListener("click", function () {
    var t = '<table border="1"><tr><th>No</th><th>Timestamp</th><th>Suhu (C)</th><th>Kelembaban (%)</th><th>Status</th></tr>';
    logRows.forEach(function (r, i) {
      t += "<tr><td>" + (i + 1) + "</td><td>" + fmtWaktu(r.waktu) + "</td><td>" + angka(r.suhu, 1) +
           "</td><td>" + angka(r.humi, 0) + "</td><td>" + r.status + "</td></tr>";
    });
    t += "</table>";
    var html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">' +
               '<head><meta charset="UTF-8"></head><body>' + t + "</body></html>";
    unduh(html, "log-suhu-kelembaban_" + stamp() + ".xls", "application/vnd.ms-excel");
  });
</script>
@endpush
