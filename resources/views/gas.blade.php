@extends('layouts.app')
@section('title', 'Gas')
@section('page-title', 'Monitoring Kualitas Udara [MQ-135]')
@section('breadcrumb', 'Gas')

@php
  // Ambang batas tiap parameter (ubah angkanya sesuai kalibrasi sensor).
  // waspada = mulai Waspada | bahaya = mulai Bahaya | max = nilai penuh progress bar
  $ambang = [
    'gas'     => ['label' => 'Gas',          'icon' => 'fa-cloud',    'unit' => 'ppm', 'waspada' => 200,  'bahaya' => 300,  'max' => 400],
    'co2'     => ['label' => 'CO2',          'icon' => 'fa-industry', 'unit' => 'ppm', 'waspada' => 1000, 'bahaya' => 2000, 'max' => 3000],
    'nh3'     => ['label' => 'NH3 (Amonia)', 'icon' => 'fa-vial',     'unit' => 'ppm', 'waspada' => 15,   'bahaya' => 25,   'max' => 40],
    'asap'    => ['label' => 'Asap',         'icon' => 'fa-fire',     'unit' => '',    'waspada' => 50,   'bahaya' => 100,  'max' => 150],
    'alkohol' => ['label' => 'Alkohol',      'icon' => 'fa-wine-glass', 'unit' => 'ppm', 'waspada' => 100, 'bahaya' => 300,  'max' => 500],
    'benzena' => ['label' => 'Benzena',      'icon' => 'fa-flask',    'unit' => 'ppm', 'waspada' => 1,    'bahaya' => 5,    'max' => 8],
  ];
@endphp

@push('styles')
<style>
  .legend-dot { display:inline-block; width:12px; height:12px; border-radius:50%; margin-right:6px; vertical-align:middle; }
  .zone { opacity:.3; }
  .bar-fill { transition: width .6s ease; pointer-events:none; }
  .ring-success { box-shadow: 0 0 0 8px rgba(49,206,54,.18); }
  .ring-warning { box-shadow: 0 0 0 8px rgba(255,173,70,.25); }
  .ring-danger  { box-shadow: 0 0 0 8px rgba(242,89,97,.20); }
</style>
@endpush

@section('content')
      {{-- ============ STATUS UTAMA ============ --}}
      <div class="row">
        <div class="col-md-12">
          <div class="card card-round border-start border-4 border-danger" id="statusCard">
            <div class="card-body d-flex align-items-center p-4">
              <div id="statusIcon"
                   class="rounded-circle d-flex align-items-center justify-content-center text-white bg-danger ring-danger me-4"
                   style="width:72px;height:72px;flex:0 0 72px;font-size:30px;">
                <i class="fas fa-exclamation-triangle"></i>
              </div>
              <div>
                <div class="text-muted">Status Udara Ruangan</div>
                <h2 class="fw-bold mb-1 text-danger" id="vStatus">-</h2>
                <small class="text-muted" id="vStatusKet">Memuat data sensor...</small>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- ============ 6 KARTU SENSOR ============ --}}
      <div class="row">
        @foreach ($ambang as $key => $a)
          <div class="col-6 col-md-4 col-xxl-2">
            <div class="card card-round border-start border-4 border-success" id="card-{{ $key }}">
              <div class="card-body p-3 d-flex align-items-center">
                <div id="ico-{{ $key }}"
                     class="rounded-circle d-flex align-items-center justify-content-center text-white bg-success me-3"
                     style="width:48px;height:48px;flex:0 0 48px;font-size:19px;">
                  <i class="fas {{ $a['icon'] }}"></i>
                </div>
                <div class="overflow-hidden">
                  <div class="text-muted small text-truncate" title="{{ $a['label'] }}">{{ $a['label'] }}</div>
                  <div class="fw-bold fs-5 text-nowrap" id="val-{{ $key }}">-</div>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>

      {{-- ============ AMBANG BATAS ============ --}}
      <div class="row">
        <div class="col-md-12">
          <div class="card card-round">
            <div class="card-header">
              <div class="card-head-row">
                <div class="card-title">Tingkat Ambang Batas Kualitas Udara</div>
                <div class="card-tools small">
                  <span class="me-3"><span class="legend-dot bg-success"></span>Aman</span>
                  <span class="me-3"><span class="legend-dot bg-warning"></span>Waspada</span>
                  <span><span class="legend-dot bg-danger"></span>Bahaya</span>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div class="row">
                @foreach ($ambang as $key => $a)
                  @php
                    $u   = $a['unit'] ? ' ' . $a['unit'] : '';
                    $pW  = round($a['waspada'] / $a['max'] * 100, 2);               // posisi batas Waspada (%)
                    $pB  = round($a['bahaya']  / $a['max'] * 100, 2);               // posisi batas Bahaya (%)
                    $zG  = $pW;                                                      // lebar zona hijau
                    $zY  = round($pB - $pW, 2);                                      // lebar zona kuning
                    $zR  = round(100 - $pB, 2);                                      // lebar zona merah
                  @endphp
                  <div class="col-md-6 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="fw-bold">
                        <i class="fas {{ $a['icon'] }} text-muted me-1"></i>{{ $a['label'] }}
                        <span class="text-muted fw-normal ms-1" id="nilai-{{ $key }}">-</span>
                      </span>
                      <span class="badge badge-success" id="badge-{{ $key }}">Aman</span>
                    </div>

                    {{-- Bar bertingkat: zona hijau/kuning/merah (pudar) + isi bar nilai saat ini --}}
                    <div class="d-flex align-items-center">
                      <div class="progress position-relative flex-grow-1" style="height:26px; overflow:hidden;">
                        <div class="progress-bar bg-success zone" style="width: {{ $zG }}%"
                             data-bs-toggle="tooltip" title="Aman: &lt; {{ $a['waspada'] . $u }}"></div>
                        <div class="progress-bar bg-warning zone" style="width: {{ $zY }}%"
                             data-bs-toggle="tooltip" title="Waspada: {{ $a['waspada'] }} &ndash; {{ $a['bahaya'] . $u }}"></div>
                        <div class="progress-bar bg-danger zone" style="width: {{ $zR }}%"
                             data-bs-toggle="tooltip" title="Bahaya: &ge; {{ $a['bahaya'] . $u }}"></div>

                        <div class="bar-fill position-absolute top-0 start-0 h-100 d-flex align-items-center bg-success text-white small fw-bold ps-2 text-nowrap"
                             id="bar-{{ $key }}" role="progressbar" style="width:0%; overflow:hidden; border-right:3px solid #fff;"
                             aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                      </div>
                      <span class="fw-bold ms-3 text-end" style="min-width:48px" id="pct-{{ $key }}">0%</span>
                    </div>

                    {{-- Penanda angka ambang di bawah bar --}}
                    <div class="position-relative text-muted mt-1" style="height:16px; font-size:11px; margin-right:60px;">
                      <span class="position-absolute" style="left:0">0</span>
                      <span class="position-absolute" style="left:{{ $pW }}%; transform:translateX(-50%)">{{ $a['waspada'] }}</span>
                      <span class="position-absolute" style="left:{{ $pB }}%; transform:translateX(-50%)">{{ $a['bahaya'] }}</span>
                      <span class="position-absolute" style="right:0">{{ $a['max'] . $u }}</span>
                    </div>

                    <small class="text-muted d-block">
                      Aman &lt; {{ $a['waspada'] . $u }} &middot;
                      Waspada {{ $a['waspada'] }}&ndash;{{ $a['bahaya'] . $u }} &middot;
                      Bahaya &ge; {{ $a['bahaya'] . $u }}
                    </small>
                  </div>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      </div>
@endsection

@push('scripts')
<script>
  var AMBANG = @json($ambang);
  var UNIT   = { gas: " ppm", co2: " ppm", nh3: " ppm", asap: "", alkohol: " ppm", benzena: " ppm" };
  var URUT   = { success: 0, warning: 1, danger: 2 };
  var NAMA   = { success: "Aman", warning: "Waspada", danger: "Bahaya" };

  function warnaStatus(k, v) {
    var a = AMBANG[k];
    return v >= a.bahaya ? "danger" : (v >= a.waspada ? "warning" : "success");
  }

  function update() {
    var d = ambilData();
    var terburuk = "success", daftar = { danger: [], warning: [] };

    Object.keys(AMBANG).forEach(function (k) {
      var a = AMBANG[k], v = d[k], w = warnaStatus(k, v);
      var pct = Math.min(100, Math.round((v / a.max) * 100));
      if (URUT[w] > URUT[terburuk]) terburuk = w;
      if (w !== "success") daftar[w].push(a.label);

      // kartu sensor (atas)
      document.getElementById("val-" + k).textContent = v + UNIT[k];
      document.getElementById("ico-" + k).className =
        "rounded-circle d-flex align-items-center justify-content-center text-white me-3 bg-" + w;
      document.getElementById("card-" + k).className =
        "card card-round border-start border-4 border-" + w;

      // ambang batas (bawah)
      var bar = document.getElementById("bar-" + k);
      bar.style.width = pct + "%";
      bar.setAttribute("aria-valuenow", pct);
      bar.className = "bar-fill position-absolute top-0 start-0 h-100 d-flex align-items-center small fw-bold ps-2 text-nowrap bg-" + w +
                      (w === "warning" ? " text-dark" : " text-white");
      bar.textContent = pct >= 22 ? v + UNIT[k] : "";

      var pEl = document.getElementById("pct-" + k);
      pEl.textContent = pct + "%";
      pEl.className = "fw-bold ms-3 text-end text-" + w;

      var badge = document.getElementById("badge-" + k);
      badge.textContent = NAMA[w];
      badge.className = "badge badge-" + w;

      document.getElementById("nilai-" + k).textContent = "(" + v + UNIT[k] + ")";
    });

    // status utama
    var ikon = { success: "fa-check-circle", warning: "fa-exclamation-circle", danger: "fa-exclamation-triangle" };
    document.getElementById("statusIcon").className =
      "rounded-circle d-flex align-items-center justify-content-center text-white me-4 bg-" + terburuk + " ring-" + terburuk;
    document.getElementById("statusIcon").innerHTML = '<i class="fas ' + ikon[terburuk] + '"></i>';
    document.getElementById("statusCard").className = "card card-round border-start border-4 border-" + terburuk;
    var st = document.getElementById("vStatus");
    st.textContent = NAMA[terburuk].toUpperCase();
    st.className = "fw-bold mb-1 text-" + terburuk;

    var ket = [];
    if (daftar.danger.length)  ket.push("Bahaya: " + daftar.danger.join(", "));
    if (daftar.warning.length) ket.push("Waspada: " + daftar.warning.join(", "));
    document.getElementById("vStatusKet").textContent =
      ket.length ? ket.join("  |  ") : "Semua parameter masih di bawah ambang batas.";
  }

  // aktifkan tooltip Bootstrap (jika tersedia); tanpa itu tetap tampil sebagai tooltip bawaan browser
  document.addEventListener("DOMContentLoaded", function () {
    if (window.bootstrap && bootstrap.Tooltip) {
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) { new bootstrap.Tooltip(el); });
    }
  });

  update();
  setInterval(update, 2000);
</script>
@endpush