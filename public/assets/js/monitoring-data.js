/*
 * Sumber data sensor. Membaca api/dashboard-realtime (sama seperti di CI4)
 * tiap 2 detik. View tidak perlu diubah: halaman tetap memanggil ambilData().
 */
var DATA_SIAP = false;
var _terbaru = { suhu: 0, humi: 0, gas: 0, co2: 0, nh3: 0, asap: 0, alkohol: 0, benzena: 0 };

// alamat dasar diambil dari lokasi file ini (…/public/assets/js/monitoring-data.js)
var URL_DATA = document.currentScript.src.split("/assets/")[0] + "/api/dashboard-realtime";

function isiData(r) {
  if (!r) return false;                       // tabel masih kosong
  _terbaru = {
    suhu: +r.suhu, humi: +r.kelembaban, gas: +r.gas, co2: +r.co2,
    nh3: +r.amonia, asap: +r.asap, alkohol: +r.alkohol, benzena: +r.benzena
  };
  return true;
}

// ambil sekali di awal (sinkron) supaya angka langsung ada saat halaman pertama tampil
(function () {
  try {
    var x = new XMLHttpRequest();
    x.open("GET", URL_DATA, false);
    x.send();
    if (x.status === 200 && isiData(JSON.parse(x.responseText))) DATA_SIAP = true;
  } catch (e) { }
})();

// lalu diperbarui tiap 2 detik
function muatTerbaru() {
  fetch(URL_DATA, { headers: { Accept: "application/json" } })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (isiData(data) && !DATA_SIAP) {
        DATA_SIAP = true;
        if (typeof update === "function") update();
      }
    })
    .catch(function () { });
}
setInterval(muatTerbaru, 2000);

function ambilData() { return _terbaru; }

var WARNA = {
  biru: "#1d7af3", hijau: "#31ce36", merah: "#f25961",
  kuning: "#ffad46", biruMuda: "#48abf7", abu: "#6c757d", gelap: "#1a2035",
};
var LABEL_GAS = ["Gas", "CO2", "NH3", "Asap", "Alkohol", "Benzena"];
function dataGasArray(d) { return [d.gas, d.co2, d.nh3, d.asap, d.alkohol, d.benzena]; }
function statusUdara(d) { return d.gas > 300 ? "BAHAYA" : (d.gas > 200 ? "WASPADA" : "AMAN"); }
