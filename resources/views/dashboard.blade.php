@extends('layouts.app')
@section('title', 'Dashboard Monitoring Ruangan')

@section('custom-header')
<div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
  <div>
    <h3 class="fw-bold mb-3">Dashboard Ruangan</h3>
    <h6 class="op-7 mb-2">Monitoring Ruangan</h6>
  </div>
</div>
@endsection

@section('content')
<div class="row">
  <div class="col-sm-6 col-md-4">
    <div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center">
      <div class="col-icon"><div class="icon-big text-center icon-primary bubble-shadow-small"><i class="fas fa-tint"></i></div></div>
      <div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Kelembapan</p><h4 class="card-title" id="valHumi">-</h4></div></div>
    </div></div></div>
  </div>
  <div class="col-sm-6 col-md-4">
    <div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center">
      <div class="col-icon"><div class="icon-big text-center icon-info bubble-shadow-small"><i class="fas fa-thermometer-half"></i></div></div>
      <div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Suhu</p><h4 class="card-title" id="valSuhu">-</h4></div></div>
    </div></div></div>
  </div>
  <div class="col-sm-12 col-md-4">
    <div class="card card-stats card-round"><div class="card-body"><div class="row align-items-center">
      <div class="col-icon"><div class="icon-big text-center icon-success bubble-shadow-small"><i class="fas fa-cloud"></i></div></div>
      <div class="col col-stats ms-3 ms-sm-0"><div class="numbers"><p class="card-category">Kualitas Udara</p><h4 class="card-title" id="valStatus">-</h4></div></div>
    </div></div></div>
  </div>
</div>

<div class="row">
  <div class="col-md-8">
    <div class="card card-round">
      <div class="card-header">
        <div class="card-head-row"><div class="card-title">Grafik Monitoring Real-time</div></div>
      </div>
      <div class="card-body">
        <div class="chart-container" style="min-height: 375px"><canvas id="statisticsChart"></canvas></div>
        <div id="myChartLegend"></div>
      </div>
    </div>
    <div class="card card-round">
      <div class="card-header">
        <div class="card-head-row"><div class="card-title">Keterangan</div></div>
      </div>
      <div class="card-body">
        <ul>
          <li>Grafik di atas menampilkan data real-time dari sensor DHT11 dan MQ-135.</li>
          <li>Data kelembapan, suhu, dan konsentrasi gas diperbarui setiap 2 detik.</li>
          <li>Kualitas udara ditentukan berdasarkan konsentrasi CO2 dan gas lainnya.</li>
          <li>Gunakan informasi ini untuk memantau kondisi ruangan dan mengambil tindakan jika diperlukan.</li>
        </ul>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card card-primary card-round">
      <div class="card-header">
        <div class="card-head-row"><div class="card-title">Gas Global</div></div>
        <div class="card-category">Konsentrasi MQ-135 (ppm)</div>
      </div>
      <div class="card-body pb-0">
        <div class="mb-4 mt-2"><h1 id="bigGas">- ppm</h1></div>
        <div class="pull-in"><canvas id="gasChart"></canvas></div>
      </div>
    </div>
    <div class="card card-round">
      <div class="card-header">
        <div class="card-head-row"><div class="card-title">Karakteristik Gas MQ-135</div></div>
      </div>
      <div class="card-body">
        <div class="chart-container" style="min-height: 300px"><canvas id="radarGas"></canvas></div>
      </div>
    </div>
  </div>
</div>


@endsection

@push('scripts')
<script>
  var MAX = 10;
  function jam(offsetDetik) { return new Date(Date.now() - offsetDetik * 1000).toLocaleTimeString("id-ID"); }

  var hist = { label: [], suhu: [], humi: [], gas: [], co2: [] };

  function seri(nama, warna, rgba, data) {
    return { label: nama, borderColor: warna, pointBackgroundColor: rgba, pointRadius: 0,
      backgroundColor: rgba, legendColor: warna, fill: true, borderWidth: 2, data: data };
  }

  // ---- area chart utama ----
  var statisticsChart = new Chart(document.getElementById("statisticsChart").getContext("2d"), {
    type: "line",
    data: { labels: hist.label, datasets: [
      seri("CO2 (PPM)", "#f3545d", "rgba(243, 84, 93, 0.4)", hist.co2),
      seri("Suhu (°C)", "#fdaf4b", "rgba(253, 175, 75, 0.4)", hist.suhu),
      seri("Kelembapan (%)", "#177dff", "rgba(23, 125, 255, 0.4)", hist.humi),
    ] },
    options: {
      responsive: true, maintainAspectRatio: false, legend: { display: false },
      tooltips: { bodySpacing: 4, mode: "nearest", intersect: 0, position: "nearest", xPadding: 10, yPadding: 10, caretPadding: 10 },
      layout: { padding: { left: 5, right: 5, top: 15, bottom: 15 } },
      scales: {
        yAxes: [{ ticks: { fontStyle: "500", beginAtZero: true, maxTicksLimit: 5, padding: 10 }, gridLines: { drawTicks: false, display: false } }],
        xAxes: [{ gridLines: { zeroLineColor: "transparent" }, ticks: { padding: 10, fontStyle: "500" } }],
      },
      legendCallback: function (chart) {
        var t = ['<ul class="' + chart.id + '-legend html-legend">'];
        chart.data.datasets.forEach(function (d) {
          t.push('<li><span style="background-color:' + d.legendColor + '"></span>' + d.label + "</li>");
        });
        t.push("</ul>");
        return t.join("");
      },
    },
  });
  var legendBox = document.getElementById("myChartLegend");
  legendBox.innerHTML = statisticsChart.generateLegend();
  Array.prototype.forEach.call(legendBox.getElementsByTagName("li"), function (li, idx) {
    li.style.cursor = "pointer";
    li.addEventListener("click", function () {
      var m = statisticsChart.getDatasetMeta(idx);
      m.hidden = m.hidden === null ? !statisticsChart.data.datasets[idx].hidden : null;
      li.style.opacity = m.hidden ? 0.4 : 1;
      statisticsChart.update();
    });
  });

  // ---- kartu biru "Gas Global" ----
  var gasChart = new Chart(document.getElementById("gasChart").getContext("2d"), {
    type: "line",
    data: { labels: hist.label, datasets: [{
      label: "Gas (ppm)", fill: true, backgroundColor: "rgba(255,255,255,0.2)", borderColor: "#fff",
      pointBorderColor: "#fff", pointBackgroundColor: "#fff", pointBorderWidth: 1, pointHoverRadius: 5,
      pointRadius: 1, pointHitRadius: 5, data: hist.gas }] },
    options: {
      maintainAspectRatio: false, legend: { display: false }, animation: { easing: "easeInOutBack" },
      scales: {
        yAxes: [{ display: false, ticks: { beginAtZero: true }, gridLines: { drawTicks: false, display: false } }],
        xAxes: [{ display: false, gridLines: { zeroLineColor: "transparent" } }],
      },
    },
  });

  // ---- radar karakteristik gas ----
  var radar = new Chart(document.getElementById("radarGas").getContext("2d"), {
    type: "radar",
    data: { labels: LABEL_GAS, datasets: [{ label: "Konsentrasi (PPM)",
      backgroundColor: "rgba(23, 125, 255, 0.25)", borderColor: "#177dff",
      pointBackgroundColor: "#177dff", borderWidth: 2, data: [0, 0, 0, 0, 0, 0] }] },
    options: {
      responsive: true, maintainAspectRatio: false, legend: { position: "top" },
      scale: { ticks: { beginAtZero: true, maxTicksLimit: 5, backdropColor: "rgba(255,255,255,0.8)" } },
    },
  });

  function update() {
    var d = ambilData(), s = statusUdara(d);
    document.getElementById("valHumi").textContent = d.humi + " %";
    document.getElementById("valSuhu").textContent = d.suhu + " °C";
    document.getElementById("valStatus").textContent = s;
    document.getElementById("bigGas").textContent = d.gas + " ppm";

    hist.label.push(jam(0)); hist.suhu.push(d.suhu); hist.humi.push(d.humi); hist.gas.push(d.gas); hist.co2.push(d.co2);
    Object.keys(hist).forEach(function (k) { if (hist[k].length > MAX) hist[k].shift(); });
    statisticsChart.update(); gasChart.update();

    radar.data.datasets[0].data = dataGasArray(d);
    radar.update();
  }
  update();
  setInterval(update, 2000);
</script>
@endpush
