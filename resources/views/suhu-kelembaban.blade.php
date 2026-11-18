@extends('layouts.app')
@section('title', 'Suhu & Kelembaban')
@section('page-title', 'Histori Data & Analisis Suhu & Kelembaban [DHT11]')
@section('breadcrumb', 'Suhu & Kelembaban')

@section('content')
      <div class="row">
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
        <div class="col-sm-12 col-md-4">
          <div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center">
            <div class="col-icon"><div class="icon-big text-center icon-success bubble-shadow-small" id="icoNyaman"><i class="fas fa-smile"></i></div></div>
            <div class="col col-stats ms-3 ms-sm-0"><div class="numbers">
              <p class="card-category">Kondisi Kenyamanan Ruangan</p>
              <h4 class="card-title"><span id="idxNyaman">-</span> <span class="badge badge-success align-middle" id="badgeNyaman">-</span></h4>
              <small class="text-muted">Keadaan aktual di ruangan saat ini</small>
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
                  <div class="card-category">10 data riwayat terbaru (tersimpan tiap 1 menit, tabel dimuat ulang otomatis)</div>
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
  var CSV_DESIMAL = ","; 
  var CSV_PEMISAH = ";"; 

  // ====================== UTILITAS ======================
  function pad(n) { return (n < 10 ? "0" : "") + n; }
  var HARI  = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];
  var BULAN = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
  function rata(arr) { return arr.reduce(function (a, b) { return a + b; }, 0) / arr.length; }

  // Penentuan status kenyamanan berdasarkan suhu aktual
  function kategoriNyaman(suhu) {
    if (suhu < 22) {
      return { teks: "Dingin / Ber-AC", warna: "info", ikon: "fa-snowflake" };
    } else if (suhu >= 22 && suhu <= 27) {
      return { teks: "Sangat Nyaman", warna: "success", ikon: "fa-smile" };
    } else if (suhu > 27 && suhu < 32) {
      return { teks: "Cukup Hangat", warna: "warning", ikon: "fa-meh" };
    } else {
      return { teks: "Nyalakan Kipas/AC", warna: "danger", ikon: "fa-frown" };
    }
  }

  // ====================== 1. STAT CARDS (DIGABUNG AGAR TIDAK BENTROK) ======================
  var URL_RINGKASAN = "{{ url('api/history-dhtmq/ringkasan') }}";
  var URL_REALTIME  = "{{ url('api/history-dhtmq/realtime') }}";

  // Fungsi untuk Card 1 & 2 (Rata-rata, Max, Min Hari Ini)
  function muatRingkasan() {
    fetch(URL_RINGKASAN, { headers: { Accept: "application/json" } })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.jumlah === 0) {
          document.getElementById("avgSuhu").textContent = "0 °C";
          document.getElementById("avgHumi").textContent = "Belum ada data hari ini";
          document.getElementById("maxMin").textContent = "-";
          document.getElementById("maxMinWaktu").textContent = "-";
          return;
        }

        // Card 1: Rata-Rata Hari Ini
        document.getElementById("avgSuhu").textContent = res.avg_suhu + " °C";
        document.getElementById("avgHumi").textContent = "Kelembaban rata-rata " + res.avg_humi + " %";

        // Card 2: Suhu Max / Min Hari Ini
        document.getElementById("maxMin").textContent = res.max_suhu + "° / " + res.min_suhu + "°";
        document.getElementById("maxMinWaktu").textContent = "Max " + res.max_waktu + " · Min " + res.min_waktu;
      });
  }

  // Fungsi khusus untuk Card 3 (Kondisi Kenyamanan Ruangan - Suhu Realtime)
  function muatSuhuRealtime() {
    fetch(URL_REALTIME, { headers: { Accept: "application/json" } })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        var suhuAktual = res.suhu; // Mengambil suhu murni saat ini
        var kat = kategoriNyaman(suhuAktual);

        // Update khusus Card 3
        document.getElementById("idxNyaman").textContent = suhuAktual.toFixed(1) + "°C";
        var b = document.getElementById("badgeNyaman");
        b.textContent = kat.teks;
        b.className = "badge badge-" + kat.warna + " align-middle";
        
        var ico = document.getElementById("icoNyaman");
        ico.className = "icon-big text-center bubble-shadow-small icon-" + kat.warna;
        ico.innerHTML = '<i class="fas ' + kat.ikon + '"></i>';
      });
  }

  // Panggil pertama kali dan jalankan polling tiap 5 detik secara terpisah
  muatRingkasan();
  muatSuhuRealtime();
  
  setInterval(muatRingkasan, 5000);
  setInterval(muatSuhuRealtime, 5000);

  // ====================== 2. GRAFIK HISTORI ======================
  // Mengambil data dari endpoint riwayat controller Anda (/api/history-dhtmq/riwayat)
  var URL_RIWAYAT = "{{ url('api/history-dhtmq/riwayat') }}";
  
  var historiChart = new Chart(document.getElementById("historiChart").getContext("2d"), {
    type: "line",
    data: {
      labels: [],
      datasets: [
        { label: "Suhu (°C)", yAxisID: "y-suhu", borderColor: "#fdaf4b", backgroundColor: "rgba(253,175,75,0.15)",
          pointBackgroundColor: "#fdaf4b", pointRadius: 3, borderWidth: 2, lineTension: 0.3, fill: false, data: [] },
        { label: "Kelembaban (%)", yAxisID: "y-humi", borderColor: "#177dff", backgroundColor: "rgba(23,125,255,0.15)",
          pointBackgroundColor: "#177dff", pointRadius: 3, borderWidth: 2, lineTension: 0.3, fill: false, data: [] },
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
            ticks: { suggestedMin: 20, suggestedMax: 50 } },
          { id: "y-humi", position: "right", scaleLabel: { display: true, labelString: "Kelembaban (%)" },
            ticks: { suggestedMin: 20, suggestedMax: 100 }, gridLines: { drawOnChartArea: false } },
        ],
        xAxes: [{ gridLines: { display: false }, ticks: { maxTicksLimit: 12, maxRotation: 0 } }],
      },
    },
  });

  function setRentang(key, namaRentang) {
    fetch(URL_RIWAYAT + "?rentang=" + key, { headers: { Accept: "application/json" } })
      .then(function(r) { return r.json(); })
      .then(function(d) {
        historiChart.data.labels = d.waktu;
        historiChart.data.datasets[0].data = d.suhu;
        historiChart.data.datasets[1].data = d.humi;
        historiChart.update();

        document.getElementById("labelRentang").textContent = namaRentang;
        var avgS = d.suhu.length > 0 ? rata(d.suhu).toFixed(1) : 0;
        var avgH = d.humi.length > 0 ? Math.round(rata(d.humi)) : 0;
        document.getElementById("infoRentang").textContent =
          namaRentang + " · " + d.suhu.length + " titik data · rata-rata " + avgS + " °C / " + avgH + " %";
      });
  }

  document.querySelectorAll("#filterRentang .dropdown-item").forEach(function (a) {
    a.addEventListener("click", function (e) { 
      e.preventDefault(); 
      var range = a.getAttribute("data-range");
      var nama = a.textContent;
      document.querySelectorAll("#filterRentang .dropdown-item").forEach(function(el) { el.classList.remove("active"); });
      a.classList.add("active");
      setRentang(range, nama);
    });
  });

  // Load awal grafik 24 jam
  setRentang("24", "24 Jam Terakhir");

  // ====================== 3. TABEL LOG & EXPORT ======================
  var URL_LOG = "{{ url('api/history-dhtmq/log') }}";
  var logRows = [];     

  function renderLog(gagal) {
    var html = "";
    if (logRows.length === 0) {
      html = '<tr><td colspan="5" class="text-center text-muted py-4">' +
             (gagal ? "Gagal memuat data dari server." : "Belum ada data riwayat.") + "</td></tr>";
    }
    logRows.forEach(function (r, i) {
      var statusLevel = (r.suhu >= 32) ? "Bahaya" : (r.suhu >= 28 ? "Waspada" : "Normal");
      var warnaBadge = (r.suhu >= 32) ? "danger" : (r.suhu >= 28 ? "warning" : "success");
      
      html += "<tr>" +
        '<td class="ps-4">' + (i + 1) + "</td>" +
        "<td>" + r.waktu + "</td>" +
        "<td>" + parseFloat(r.suhu).toFixed(1) + " °C</td>" +
        "<td>" + Math.round(r.humi) + " %</td>" +
        '<td><span class="badge badge-' + warnaBadge + '">' + statusLevel + "</span></td>" +
        "</tr>";
    });
    document.getElementById("logBody").innerHTML = html;
  }

  function muatLog() {
    fetch(URL_LOG, { headers: { Accept: "application/json" } })
      .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
      .then(function (data) {
        logRows = data;
        renderLog();
      })
      .catch(function () { renderLog(true); });   
  }

  muatLog();
  setInterval(muatLog, 5000);

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
      var statusLevel = (r.suhu >= 32) ? "Bahaya" : (r.suhu >= 28 ? "Waspada" : "Normal");
      isi.push([i + 1, r.waktu, angka(+r.suhu, 1), angka(+r.humi, 0), statusLevel].join(CSV_PEMISAH));
    });
    unduh(isi.join("\r\n"), "log-suhu-kelembaban_" + stamp() + ".csv", "text/csv");
  });

  document.getElementById("btnExcel").addEventListener("click", function () {
    var t = '<table border="1"><tr><th>No</th><th>Timestamp</th><th>Suhu (C)</th><th>Kelembaban (%)</th><th>Status</th></tr>';
    logRows.forEach(function (r, i) {
      var statusLevel = (r.suhu >= 32) ? "Bahaya" : (r.suhu >= 28 ? "Waspada" : "Normal");
      t += "<tr><td>" + (i + 1) + "</td><td>" + r.waktu + "</td><td>" + angka(+r.suhu, 1) +
           "</td><td>" + angka(+r.humi, 0) + "</td><td>" + statusLevel + "</td></tr>";
    });
    t += "</table>";
    var html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">' +
               '<head><meta charset="UTF-8"></head><body>' + t + "</body></html>";
    unduh(html, "log-suhu-kelembaban_" + stamp() + ".xls", "application/vnd.ms-excel");
  });
</script>
@endpush