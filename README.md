<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Monitoring Ruangan (DHT11 + MQ-135 + ESP8266)

Dashboard monitoring suhu, kelembaban, dan kualitas udara berbasis **Laravel 12**
dengan template **KaiAdmin Lite**. Data dikirim oleh **NodeMCU ESP8266** lewat URL (HTTP GET),
disimpan ke MySQL, lalu ditampilkan di dashboard secara real-time.

## Alur data

```
ESP8266 --GET--> /api/terima-data/{suhu}/{kelembaban}/{gas}/{co2}/{amonia}/{benzena}/{alkohol}/{asap}
                      |
                      v  ControllerDhtmq::update()
        +-------------+------------------------------+
        |                                            |
 sensor_dhtmq (1 baris, id 1, ditimpa)      history_dhtmq (1 baris per menit)
        |                                            |
 /api/dashboard-realtime  (dibaca JS tiap 2 dtk)     /api/history-dhtmq/... (grafik, ringkasan, log)
        |
 Dashboard (/) dan halaman Gas (/udara)
```

Alurnya sama seperti versi CI4: route menangkap URL, controller menyimpan ke database,
controller lain membaca database dan mengirimnya ke view.

---

## 1. Prasyarat

| Kebutuhan | Keterangan |
|---|---|
| XAMPP | Apache + MySQL, PHP **8.2** atau lebih baru |
| Composer | https://getcomposer.org |
| Git | https://git-scm.com |
| ESP8266 + sketch | Komputer dan ESP8266 harus di **Wi-Fi yang sama** |

Node.js / `npm` **tidak diperlukan**, karena semua aset template sudah ada di `public/assets`.

## 2. Clone project

Letakkan project di folder `htdocs` XAMPP supaya bisa dibuka lewat Apache:

```
cd C:\xampp\htdocs
git clone https://github.com/<username>/<nama-repo>.git dhtmq
cd dhtmq
```

Nama folder `dhtmq` menentukan alamat akhirnya: `http://<IP>/dhtmq/public`.
Kalau memakai nama folder lain, sesuaikan alamat di langkah 5.

## 3. Install dependensi

```
composer install
```

Perintah ini membuat folder `vendor/`. Folder itu tidak ikut di GitHub.

## 4. Buat file `.env` dan kunci aplikasi

```
copy .env.example .env
php artisan key:generate
```

`key:generate` mengisi `APP_KEY` di `.env`. Tanpa itu, halaman akan menampilkan error
*No application encryption key*.

## 5. Atur `.env`

Buka `.env` lalu ubah baris-baris berikut.

```dotenv
APP_NAME="Monitoring Ruangan"
APP_ENV=local
APP_DEBUG=true
APP_TIMEZONE=Asia/Jakarta
APP_URL=http://192.168.1.233/dhtmq/public
ASSET_URL=http://192.168.1.233/dhtmq/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_dhtmq
DB_USERNAME=root
DB_PASSWORD=
```

Penjelasan tiap baris:

| Baris | Fungsi |
|---|---|
| `APP_URL` | Alamat tempat aplikasi dibuka. Formatnya `http://<IP-KOMPUTER>/<folder>/public`. |
| `ASSET_URL` | Alamat dasar untuk CSS, JS, dan gambar. Wajib diisi **sama dengan `APP_URL`** karena aplikasi berada di subfolder `/dhtmq/public`. Kalau kosong, tampilan bisa tanpa style. |
| `APP_TIMEZONE` | Zona waktu. Tanpa ini, kolom `waktu` tersimpan dengan jam UTC (selisih 7 jam) dan filter "hari ini" bergeser. |
| `DB_DATABASE` | Nama database: `db_dhtmq`. |
| `DB_USERNAME` / `DB_PASSWORD` | Bawaan XAMPP: `root` dan password kosong. |

**Mencari IP komputer:** buka terminal, jalankan `ipconfig`, lalu lihat **IPv4 Address**
pada bagian adapter Wi-Fi. Ganti `192.168.1.233` di atas dengan IP itu.

Setiap kali `.env` diubah, jalankan:

```
php artisan config:clear
```

> **IP bisa berubah** kalau router membagikan IP baru. Kalau itu terjadi, ubah `APP_URL`,
> `ASSET_URL`, dan `host` di sketch ESP8266. Lebih aman memasang IP statis di komputer
> atau reservasi IP di router.

Untuk dibuka dari komputer yang sama saja, `http://localhost/dhtmq/public` juga bisa dipakai.
Tetapi ESP8266 tetap membutuhkan IP.

## 6. Siapkan database

1. Buka **XAMPP Control Panel**, nyalakan **Apache** dan **MySQL** (status hijau).
2. Buat database lewat phpMyAdmin (`http://localhost/phpmyadmin`):
   klik **Baru**, isi nama `db_dhtmq`, collation `utf8mb4_unicode_ci`, lalu **Buat**.

   Atau lewat tab **SQL**:
   ```sql
   CREATE DATABASE db_dhtmq CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

Kalau langkah ini terlewat, Laravel akan menanyakan "database belum ada, mau dibuat?" saat
`migrate`. Jawab **yes**.

## 7. Jalankan migrasi

```
php artisan migrate
```

Tabel yang terbentuk:

| Tabel | Fungsi |
|---|---|
| `sensor_dhtmq` | Data sensor **saat ini** (satu baris, `id = 1`, ditimpa tiap kiriman) |
| `history_dhtmq` | Riwayat sensor (satu baris per menit) |
| `users`, `sessions`, `cache`, `jobs`, ... | Tabel bawaan Laravel |

Cek di phpMyAdmin bahwa `sensor_dhtmq` dan `history_dhtmq` ada.
Kalau salah satu tidak ada, struktur yang diharapkan ada di bagian **Struktur tabel** di bawah.

## 8. Buka aplikasi

Pastikan Apache dan MySQL menyala, lalu buka:

| Halaman | Alamat |
|---|---|
| Dashboard | `http://192.168.1.233/dhtmq/public/` |
| Suhu & Kelembaban | `http://192.168.1.233/dhtmq/public/sensor` |
| Gas / Kualitas Udara | `http://192.168.1.233/dhtmq/public/udara` |

Kalau muncul halaman error Apache (bukan error Laravel), cek bagian **Troubleshooting**.

**Alternatif tanpa Apache:**

```
php artisan serve --host=0.0.0.0 --port=8000
```

Lalu isi `APP_URL=http://<IP>:8000`, kosongkan `ASSET_URL`, dan buka `http://<IP>:8000`.
Dengan cara ini alamat tidak memakai `/dhtmq/public`.

## 9. Tes kirim data tanpa alat

Buka di browser (urutan angka: suhu, kelembaban, gas, co2, amonia, benzena, alkohol, asap):

```
http://192.168.1.233/dhtmq/public/api/terima-data/31.8/62/345/16/19.2/4.4/9/16
```

Harus tampil **OK**. Cek hasilnya:
- `sensor_dhtmq` berisi 1 baris (id 1), nilainya berubah tiap kamu kirim data baru.
- `history_dhtmq` bertambah 1 baris, lalu baris baru hanya muncul tiap sekitar satu menit.
- `http://192.168.1.233/dhtmq/public/api/dashboard-realtime` menampilkan JSON data terakhir.

Simulasi sensor terus-menerus (PowerShell, terminal baru). Tekan Ctrl+C untuk berhenti:

```powershell
while ($true) {
  $suhu = [math]::Round(30 + (Get-Random -Minimum 0 -Maximum 40) / 10, 1)
  $humi = Get-Random -Minimum 55 -Maximum 70
  $gas  = Get-Random -Minimum 140 -Maximum 360
  Invoke-WebRequest "http://192.168.1.233/dhtmq/public/api/terima-data/$suhu/$humi/$gas/16/19.2/4.4/9/16" -UseBasicParsing | Out-Null
  Write-Host "Terkirim: suhu $suhu, humi $humi, gas $gas"
  Start-Sleep -Seconds 2
}
```

## 10. Hubungkan ESP8266

Di sketch, ubah bagian ini:

```cpp
const char* ssid     = "NAMA_WIFI";
const char* password = "PASSWORD_WIFI";
const char* host     = "192.168.1.233";   // IP komputer (hasil ipconfig)
```

Alamat tujuannya harus mengandung **`/api`**:

```cpp
String Link = "http://" + String(host) + "/dhtmq/public/api/terima-data/"
            + String(suhu, 1) + "/" + String(hum, 0) + "/" + String(gas) + "/"
            + String(ppm_co2, 1) + "/" + String(ppm_amonia, 1) + "/"
            + String(ppm_benzena, 1) + "/" + String(ppm_alkohol, 1) + "/"
            + String(ppm_asap, 1);
```

Di Serial Monitor harus tampil `HTTP Code: 200`, dan footer TFT menampilkan `HTTP 200` berwarna hijau.

> **Jangan simpan SSID dan password Wi-Fi asli di repository publik.**

---

## Endpoint

Route halaman (`routes/web.php`):

| URL | Fungsi |
|---|---|
| `/` dan `/dashboard` | Dashboard |
| `/sensor` | Halaman Suhu & Kelembaban |
| `/udara` | Halaman Gas / Kualitas Udara |

Route data (`routes/api.php`, otomatis berawalan `/api` dan tanpa session):

| URL | Fungsi |
|---|---|
| `/api/terima-data/{suhu}/{kelembaban}/{gas}/{co2}/{amonia}/{benzena}/{alkohol}/{asap}` | ESP8266 mengirim data |
| `/api/dashboard-realtime` | Data terakhir (JSON), dibaca halaman tiap 2 detik |
| `/api/history-dhtmq/riwayat?rentang=24` | Rata-rata suhu dan kelembaban; `rentang` = `24` (per jam), `7` (per 6 jam), `30` (per hari) |
| `/api/history-dhtmq/ringkasan` | Rata-rata, suhu max dan min hari ini |
| `/api/history-dhtmq/log` | 10 data history terakhir |

`routes/api.php` didaftarkan lewat baris `api: __DIR__.'/../routes/api.php'` di `bootstrap/app.php`.

## Struktur project (bagian yang dipakai)

```
app/
  Http/Controllers/
    ControllerDhtmq.php          # halaman, terima data ESP, data realtime
    ControllerHistoryDhtmq.php   # data history
  Models/
    SensorDhtmq.php              # tabel sensor_dhtmq
    HistoryDhtmq.php             # tabel history_dhtmq
bootstrap/app.php                # mendaftarkan routes/api.php
database/migrations/             # tabel sensor dan history
public/assets/                   # template KaiAdmin Lite + js/monitoring-data.js
resources/views/
  layouts/app.blade.php          # kerangka halaman
  partials/                      # sidebar, navbar, footer
  dashboard.blade.php
  suhu-kelembaban.blade.php
  gas.blade.php
routes/web.php, routes/api.php
```

`public/assets/js/monitoring-data.js` mengambil data dari `/api/dashboard-realtime` tiap 2 detik
dan menyediakannya ke halaman lewat fungsi `ambilData()`. Alamat API dihitung dari lokasi file itu,
jadi tidak perlu diatur lagi saat `APP_URL` berubah.

## Struktur tabel

Kalau `php artisan migrate` tidak membuat tabelnya dengan benar, buat manual lewat tab **SQL**:

```sql
CREATE TABLE sensor_dhtmq (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  suhu DECIMAL(5,1) NOT NULL,
  kelembaban DECIMAL(5,1) NOT NULL,
  gas DECIMAL(8,2) NOT NULL DEFAULT 0,
  co2 DECIMAL(8,2) NOT NULL DEFAULT 0,
  amonia DECIMAL(8,2) NOT NULL DEFAULT 0,
  benzena DECIMAL(8,2) NOT NULL DEFAULT 0,
  alkohol DECIMAL(8,2) NOT NULL DEFAULT 0,
  asap DECIMAL(8,2) NOT NULL DEFAULT 0,
  waktu TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE history_dhtmq (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  suhu DECIMAL(5,1) NOT NULL,
  kelembaban DECIMAL(5,1) NOT NULL,
  gas DECIMAL(8,2) NOT NULL DEFAULT 0,
  co2 DECIMAL(8,2) NOT NULL DEFAULT 0,
  amonia DECIMAL(8,2) NOT NULL DEFAULT 0,
  benzena DECIMAL(8,2) NOT NULL DEFAULT 0,
  alkohol DECIMAL(8,2) NOT NULL DEFAULT 0,
  asap DECIMAL(8,2) NOT NULL DEFAULT 0,
  waktu TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (waktu)
);
```

---

## Troubleshooting

| Gejala | Penyebab dan solusi |
|---|---|
| Halaman error Apache ("Internal Server Error"), bukan halaman Laravel | Baca baris terakhir `C:\xampp\apache\logs\error.log`. Biasanya `mod_rewrite` belum aktif (`LoadModule rewrite_module` di `httpd.conf`) atau `AllowOverride None` (ubah jadi `All`). Pastikan file `public/.htaccess` ada. |
| Tampilan tanpa style / tanpa ikon | `ASSET_URL` kosong atau tidak sama dengan `APP_URL`. Perbaiki, lalu `php artisan config:clear`. |
| *No application encryption key* | Jalankan `php artisan key:generate`. |
| *Class "..." not found* atau *does not exist* | Nama file harus sama persis dengan nama class (`ControllerDhtmq.php`, huruf besar-kecil berpengaruh di Linux). Periksa juga baris `use` di atas file. |
| Alamat `/api/...` menghasilkan 404 | Baris `api:` di `bootstrap/app.php` belum ada, atau cache route lama. Jalankan `php artisan route:clear` lalu `php artisan route:list --path=api`. |
| *Unknown column '...' in 'field list'* | Nama kolom di tabel berbeda dengan yang dipakai kode. Cocokkan dengan bagian **Struktur tabel**. |
| *Table '...' doesn't exist* | Migrasi belum dijalankan atau nama tabel salah (`php artisan migrate:status`). |
| Jam di tabel selisih 7 jam | `APP_TIMEZONE=Asia/Jakarta` belum diisi di `.env`. |
| Dashboard kosong / tampil "-" | Tabel `sensor_dhtmq` masih kosong. Kirim satu data (langkah 9), lalu buka `/api/dashboard-realtime` untuk memastikan JSON-nya tampil. Tekan Ctrl+F5, lalu cek tab Console (F12). |
| ESP8266: `HTTP 404` | Alamat di sketch belum memakai `/api/terima-data/`. |
| ESP8266: `HTTP -1` | ESP tidak bisa tersambung. Cocokkan IP (`ipconfig`), pastikan satu Wi-Fi, dan izinkan **Apache HTTP Server** di Windows Firewall (Private). Bisa juga ditambah `http.setReuse(false);` sebelum `http.begin(...)`. |
| ESP8266: `HTTP 500` | Lihat baris paling bawah `storage/logs/laravel.log`. |

Untuk melihat error Laravel lebih jelas saat pengembangan, pastikan `APP_DEBUG=true` di `.env`.
Untuk pemakaian sungguhan, ubah menjadi `APP_DEBUG=false`.

## Status pengembangan

Sudah berjalan:
- Pengiriman data ESP8266 ke `sensor_dhtmq` dan `history_dhtmq`.
- Dashboard dan halaman Gas membaca data real-time dari database.
- Endpoint history (rata-rata per jam, ringkasan harian, log).

Belum selesai:
- Halaman **Suhu & Kelembaban** (`/sensor`): kartu ringkasan, grafik tren, filter, dan tabel log
  masih memakai data contoh, belum tersambung ke endpoint `api/history-dhtmq/...`.
- Ambang batas pada halaman **Gas** masih angka perkiraan dan perlu disesuaikan dengan
  nilai sensor MQ-135 yang sebenarnya.

## Kredit

Template antarmuka: **KaiAdmin Lite** (ThemeKita). Framework: **Laravel**.
