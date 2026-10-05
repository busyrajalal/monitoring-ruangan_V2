/*
 * Sumber data sensor (dummy / frontend only).
 * Nanti cukup ganti isi fungsi ini dengan fetch() ke route Laravel,
 * contoh:  const r = await fetch('/api/sensor'); return await r.json();
 * Format data yang diharapkan halaman:
 *   { suhu, humi, gas, co2, nh3, asap, alkohol, benzena }
 */
function ambilData() {
  // Dummy: sedikit variasi acak supaya grafik terlihat hidup
  var j = function (v, r) { return +(v + (Math.random() - 0.5) * r).toFixed(1); };
  return {
    suhu: j(31.8, 0.6), humi: Math.round(j(62, 2)),
    gas: Math.round(j(345, 10)), co2: Math.round(j(16, 2)),
    nh3: j(19.2, 1), asap: Math.round(j(16, 2)),
    alkohol: Math.round(j(9, 1)), benzena: j(4.4, 0.4),
  };
}

var WARNA = {
  biru: "#1d7af3", hijau: "#31ce36", merah: "#f25961",
  kuning: "#ffad46", biruMuda: "#48abf7", abu: "#6c757d", gelap: "#1a2035",
};
var LABEL_GAS = ["Gas", "CO2", "NH3", "Asap", "Alkohol", "Benzena"];
function dataGasArray(d) { return [d.gas, d.co2, d.nh3, d.asap, d.alkohol, d.benzena]; }
function statusUdara(d) { return d.gas > 300 ? "BAHAYA" : (d.gas > 200 ? "WASPADA" : "AMAN"); }
