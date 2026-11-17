#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClient.h>
#include <DHT.h>
#include <Adafruit_GFX.h>
#include <Adafruit_ILI9341.h>

/* ============================ WIFI & SERVER ========================== */
const char* ssid     = "Nama WiFi";        // <- ganti
const char* password = "Sandi WiFi";    // <- ganti
const char* host     = "IP kalian";         // <- ganti IP server Anda

/* ============================== PINOUT =============================== */
#define TFT_CS    D8    // GPIO15
#define TFT_DC    D3    // GPIO0
#define TFT_RST   D4    // GPIO2

#define DHTPIN    D1    // GPIO5
#define DHTTYPE   DHT11
#define GAS_PIN   A0
#define BUZZER_PIN D2   // GPIO4

/* ============================== OBJEK ================================ */
Adafruit_ILI9341 tft = Adafruit_ILI9341(TFT_CS, TFT_DC, TFT_RST);
DHT dht(DHTPIN, DHTTYPE);
WiFiClient client;

/* ============================== WARNA ================================ */
#define C_BG      0x0861   // navy gelap
#define C_CARD    0x18E3   // latar kartu
#define C_TRACK   0x39E7   // latar progress bar
#define C_HEADER  0x02B5   // header biru
#define C_HEADER2 0x0339
#define C_TEXT    0xFFFF
#define C_MUTED   0x94B2
#define C_DIM     0x6B6D
#define C_ORANGE  0xFD20
#define C_CYAN    0x07FF
#define C_GREEN   0x2FE5
#define C_RED     0xF800
#define C_YELLOW  0xFFE0

/* ============================ AMBANG BATAS =========================== */
const int   AMBANG_RAW_GAS = 400;
const float AMBANG_ASAP    = 300.0;
const float AMBANG_SUHU    = 38.0;
const float SUHU_MIN       = 15.0;
const float LEMBAB_MIN     = 30.0;
const float LEMBAB_MAX     = 70.0;

/* ============================ LAYOUT KARTU =========================== */
const int CW = 151, CH = 78;
const int X1 = 6,   X2 = 163;       // kolom kiri & kanan
const int Y1 = 38,  Y2 = 122;       // baris atas & bawah

/* ========================== VARIABEL GLOBAL ========================== */
float suhu = 0, hum = 0;
int   gas = 0;
float ppm_co2 = 0, ppm_amonia = 0, ppm_benzena = 0, ppm_alkohol = 0, ppm_asap = 0;
bool  isWarning = false;
int   lastHttp = 0;

unsigned long lastRead  = 0;
unsigned long lastBlink = 0;
unsigned long lastClock = 0;
bool          blinkState = false;

// cache untuk anti-flicker
int    prevFill[3] = {-1, -1, -1};
uint16_t prevCol[3] = {0, 0, 0};
int    prevMode = -1;
int    prevWifi = -1;
String prevReason = "";

/* ============================ PROTOTYPE ============================== */
void drawSplash();
void drawDashboardFrame();
void drawCard(int x, int y, int w, int h, uint16_t accent);
void drawThermometerIcon(int x, int y);
void drawDropIcon(int x, int y);
void drawGasIcon(int x, int y);
void drawBar(int id, int x, int y, int w, int h, float v, float mn, float mx, uint16_t col);
void drawCaption(int x, int y, const char* txt, uint16_t col);
void drawWifi(int level);
int  wifiLevel();
void updateClock();
void updateValues();
void updateReason();
void updateStatus(bool blinkOn);
void updateFooter();
void setFooterLine2(String s, uint16_t col);
String buildReason();

/* ================================ SETUP ============================== */
void setup() {
  Serial.begin(115200);

  tft.begin();
  tft.setRotation(1);              // Landscape 320x240
  drawSplash();

  dht.begin();
  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(BUZZER_PIN, LOW);

  drawDashboardFrame();

  WiFi.begin(ssid, password);
  Serial.print("SAMBUNG WIFI...");
  setFooterLine2("Menghubungkan WiFi...", C_YELLOW);

  unsigned long tStart = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - tStart < 20000) {
    delay(500);
    Serial.print(".");
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWIFI TERHUBUNG!");
    Serial.print("IP: ");
    Serial.println(WiFi.localIP());
    setFooterLine2("WiFi OK  " + WiFi.localIP().toString(), C_GREEN);
  } else {
    Serial.println("\nWIFI GAGAL!");
    setFooterLine2("WiFi GAGAL - cek SSID/password", C_RED);
  }
  drawWifi(wifiLevel());
  updateClock();
  delay(800);
  lastRead = millis() - 2000;      // baca sensor langsung di loop pertama
}

/* ================================= LOOP ============================== */
void loop() {
  unsigned long now = millis();

  /* --- jam uptime tiap 1 detik --- */
  if (now - lastClock >= 1000) {
    lastClock = now;
    updateClock();
  }

  /* --- baca & kirim tiap 2 detik --- */
  if (now - lastRead >= 2000) {
    lastRead = now;

    suhu = dht.readTemperature();
    hum  = dht.readHumidity();
    gas  = analogRead(GAS_PIN);

    if (isnan(suhu) || isnan(hum)) {
      Serial.println("SENSOR ERROR! CEK DHT11");
      setFooterLine2("Sensor DHT11 error!", C_RED);
      return;
    }

    int gas_calc = (gas < 1) ? 1 : gas;
    float rs_ro = (1023.0 / (float)gas_calc) - 1.0;
    if (rs_ro < 0.1) rs_ro = 0.1;

    ppm_co2     = 110.47 * pow(rs_ro, -2.862);
    ppm_amonia  = 102.20 * pow(rs_ro, -2.473);
    ppm_benzena =  44.95 * pow(rs_ro, -3.424);
    ppm_alkohol =  77.26 * pow(rs_ro, -3.180);
    ppm_asap    = 159.60 * pow(rs_ro, -3.407);

    isWarning = (gas > AMBANG_RAW_GAS ||
                 ppm_asap > AMBANG_ASAP ||
                 suhu > AMBANG_SUHU ||
                 suhu < SUHU_MIN ||
                 hum < LEMBAB_MIN ||
                 hum > LEMBAB_MAX);

    // Update tampilan
    updateValues();
    updateReason();
    if (!isWarning) updateStatus(false);
    drawWifi(wifiLevel());

    // Serial log
    Serial.println("=================================");
    Serial.printf("Suhu    : %.1f C\n", suhu);
    Serial.printf("Lembab  : %.0f %%\n", hum);
    Serial.printf("CO2     : %.1f PPM\n", ppm_co2);
    Serial.printf("NH3     : %.1f PPM\n", ppm_amonia);
    Serial.printf("Benzena : %.1f PPM\n", ppm_benzena);
    Serial.printf("Alkohol : %.1f PPM\n", ppm_alkohol);
    Serial.printf("Asap    : %.1f PPM\n", ppm_asap);
    Serial.printf("Gas Raw : %d\n", gas);

    // Kirim ke server
    if (WiFi.status() == WL_CONNECTED) {
      HTTPClient http;
      String Link = "http://" + String(host) + "/dhtmq/public/api/terima-data/"
                  + String(suhu, 1) + "/"
                  + String(hum, 0)  + "/"
                  + String(gas)     + "/"
                  + String(ppm_co2, 1)     + "/"
                  + String(ppm_amonia, 1)  + "/"
                  + String(ppm_benzena, 1) + "/"
                  + String(ppm_alkohol, 1) + "/"
                  + String(ppm_asap, 1);

      http.begin(client, Link);
      lastHttp = http.GET();
      Serial.println("Link URL : " + Link);
      Serial.printf("HTTP Code: %d\n", lastHttp);
      Serial.println(http.errorToString(lastHttp));
      http.end();
    } else {
      lastHttp = 0;
    }
    updateFooter();
  }

  /* --- Buzzer + blink warning (non-blocking) --- */
  if (isWarning) {
    if (now - lastBlink >= 400) {
      lastBlink = now;
      blinkState = !blinkState;
      updateStatus(blinkState);
      digitalWrite(BUZZER_PIN, blinkState ? HIGH : LOW);
    }
  } else {
    digitalWrite(BUZZER_PIN, LOW);
  }
}

/* ============================== SPLASH =============================== */
void drawSplash() {
  tft.fillScreen(C_BG);

  // logo
  tft.fillRoundRect(120, 40, 80, 80, 18, C_HEADER);
  tft.fillRoundRect(126, 46, 68, 68, 14, C_CARD);
  int px[] = {132, 146, 152, 160, 168, 174, 188};
  int py[] = { 80,  80,  64,  96,  72,  80,  80};
  for (int i = 0; i < 6; i++) {
    tft.drawLine(px[i], py[i],     px[i + 1], py[i + 1],     C_CYAN);
    tft.drawLine(px[i], py[i] + 1, px[i + 1], py[i + 1] + 1, C_CYAN);
  }

  tft.setTextColor(C_TEXT);
  tft.setTextSize(2);
  tft.setCursor(94, 140);
  tft.print("ENV MONITOR");

  tft.setTextSize(1);
  tft.setTextColor(C_MUTED);
  tft.setCursor(88, 166);
  tft.print("DHT11 + MQ-135 + ILI9341");
  tft.setCursor(109, 198);
  tft.print("Booting system...");

  tft.drawRoundRect(60, 214, 200, 8, 4, C_MUTED);
  for (int i = 8; i <= 196; i += 4) {
    tft.fillRoundRect(62, 216, i, 4, 2, C_CYAN);
    delay(8);
  }
  delay(200);
}

/* ============================ KERANGKA UI ============================ */
void drawDashboardFrame() {
  tft.fillScreen(C_BG);

  /* Header */
  tft.fillRect(0, 0, 320, 30, C_HEADER);
  tft.fillRect(0, 30, 320, 2, C_CYAN);
  tft.setTextColor(C_TEXT);
  tft.setTextSize(2);
  tft.setCursor(8, 8);
  tft.print("ENV MONITOR");

  /* Kartu Suhu */
  drawCard(X1, Y1, CW, CH, C_ORANGE);
  drawThermometerIcon(X1 + CW - 34, Y1 + 8);
  tft.setTextSize(1);
  tft.setTextColor(C_MUTED);
  tft.setCursor(X1 + 10, Y1 + 8);
  tft.print("TEMPERATURE");
  tft.drawCircle(X1 + 90, Y1 + 28, 2, C_MUTED);       // simbol derajat
  tft.setTextSize(2);
  tft.setCursor(X1 + 95, Y1 + 28);
  tft.print("C");

  /* Kartu Kelembaban */
  drawCard(X2, Y1, CW, CH, C_CYAN);
  drawDropIcon(X2 + CW - 34, Y1 + 8);
  tft.setTextSize(1);
  tft.setTextColor(C_MUTED);
  tft.setCursor(X2 + 10, Y1 + 8);
  tft.print("HUMIDITY");
  tft.setTextSize(2);
  tft.setCursor(X2 + 86, Y1 + 28);
  tft.print("%");

  /* Kartu Gas */
  drawCard(X1, Y2, CW, CH, C_YELLOW);
  drawGasIcon(X1 + CW - 46, Y2 + 8);
  tft.setTextSize(1);
  tft.setTextColor(C_MUTED);
  tft.setCursor(X1 + 10, Y2 + 8);
  tft.print("AIR QUALITY");
  tft.setCursor(X1 + 86, Y2 + 38);
  tft.print("ADC");

  /* Kartu Status */
  drawCard(X2, Y2, CW, CH, C_GREEN);
  tft.setTextSize(1);
  tft.setTextColor(C_MUTED);
  tft.setCursor(X2 + 10, Y2 + 8);
  tft.print("SYSTEM STATUS");

  /* Footer */
  tft.fillRoundRect(6, 206, 308, 30, 6, C_CARD);
  tft.drawRoundRect(6, 206, 308, 30, 6, C_TRACK);
}

void drawCard(int x, int y, int w, int h, uint16_t accent) {
  tft.fillRoundRect(x, y, w, h, 8, C_CARD);
  tft.drawRoundRect(x, y, w, h, 8, accent);
  tft.drawRoundRect(x + 1, y + 1, w - 2, h - 2, 7, accent);
}

/* ================================ IKON =============================== */
void drawThermometerIcon(int x, int y) {
  tft.fillRoundRect(x + 8, y, 8, 22, 4, C_ORANGE);
  tft.fillCircle(x + 12, y + 26, 7, C_ORANGE);
  tft.fillRect(x + 10, y + 6, 4, 16, C_CARD);
  tft.fillCircle(x + 12, y + 26, 3, C_CARD);
  tft.fillRect(x + 11, y + 14, 2, 12, C_RED);
}

void drawDropIcon(int x, int y) {
  tft.fillCircle(x + 12, y + 20, 9, C_CYAN);
  tft.fillTriangle(x + 12, y, x + 4, y + 15, x + 20, y + 15, C_CYAN);
  tft.fillCircle(x + 9, y + 21, 2, C_CARD);
}

void drawGasIcon(int x, int y) {
  tft.fillCircle(x + 8,  y + 22, 6, C_YELLOW);
  tft.fillCircle(x + 18, y + 16, 9, C_YELLOW);
  tft.fillCircle(x + 29, y + 22, 6, C_YELLOW);
  tft.fillRect(x + 8, y + 22, 21, 6, C_YELLOW);
}

/* ============================== KOMPONEN ============================= */
void drawBar(int id, int x, int y, int w, int h, float v, float mn, float mx, uint16_t col) {
  float r = (constrain(v, mn, mx) - mn) / (mx - mn);
  int f = (int)(r * w);
  if (f == prevFill[id] && col == prevCol[id]) return;   // tidak berubah
  prevFill[id] = f;
  prevCol[id]  = col;

  tft.fillRoundRect(x, y, w, h, h / 2, C_TRACK);
  if (f >= h)      tft.fillRoundRect(x, y, f, h, h / 2, col);
  else if (f > 0)  tft.fillRect(x, y, f, h, col);
}

void drawCaption(int x, int y, const char* txt, uint16_t col) {
  char buf[20];
  snprintf(buf, sizeof(buf), "%-16.16s", txt);
  tft.fillCircle(x + 3, y + 3, 3, col);
  tft.setTextSize(1);
  tft.setTextColor(col, C_CARD);
  tft.setCursor(x + 12, y);
  tft.print(buf);
}

int wifiLevel() {
  if (WiFi.status() != WL_CONNECTED) return 0;
  long r = WiFi.RSSI();
  if (r > -55) return 4;
  if (r > -65) return 3;
  if (r > -75) return 2;
  return 1;
}

void drawWifi(int level) {
  if (level == prevWifi) return;
  prevWifi = level;
  tft.fillRect(280, 4, 32, 24, C_HEADER);
  for (int i = 0; i < 4; i++) {
    int h = 4 + i * 4;
    uint16_t c = (i < level) ? C_TEXT : C_DIM;
    tft.fillRect(282 + i * 7, 26 - h, 5, h, c);
  }
}

void updateClock() {
  unsigned long s = millis() / 1000;
  char buf[16];
  snprintf(buf, sizeof(buf), "UP %02lu:%02lu:%02lu",
           (s / 3600) % 100, (s / 60) % 60, s % 60);
  tft.setTextSize(1);
  tft.setTextColor(C_TEXT, C_HEADER);
  tft.setCursor(200, 11);
  tft.print(buf);
}

/* ========================== UPDATE NILAI SENSOR ====================== */
void updateValues() {
  char t[10], buf[10];

  /* ---- Suhu ---- */
  dtostrf(suhu, 1, 1, t);
  snprintf(buf, sizeof(buf), "%-4s", t);
  tft.setTextSize(3);
  tft.setTextColor(C_ORANGE, C_CARD);
  tft.setCursor(X1 + 10, Y1 + 24);
  tft.print(buf);

  const char* capT = "NYAMAN";
  uint16_t colT = C_GREEN;
  if (suhu > AMBANG_SUHU)      { capT = "PANAS!";  colT = C_RED;  }
  else if (suhu < SUHU_MIN)    { capT = "DINGIN";  colT = C_CYAN; }
  drawBar(0, X1 + 10, Y1 + 52, CW - 20, 6, suhu, 0, 50, colT);
  drawCaption(X1 + 10, Y1 + 64, capT, colT);

  /* ---- Kelembaban ---- */
  snprintf(buf, sizeof(buf), "%-4d", (int)(hum + 0.5));
  tft.setTextSize(3);
  tft.setTextColor(C_CYAN, C_CARD);
  tft.setCursor(X2 + 10, Y1 + 24);
  tft.print(buf);

  const char* capH = "IDEAL";
  uint16_t colH = C_GREEN;
  if (hum > LEMBAB_MAX)        { capH = "TERLALU LEMBAB"; colH = C_RED;    }
  else if (hum < LEMBAB_MIN)   { capH = "KERING";         colH = C_YELLOW; }
  drawBar(1, X2 + 10, Y1 + 52, CW - 20, 6, hum, 0, 100, colH);
  drawCaption(X2 + 10, Y1 + 64, capH, colH);

  /* ---- Gas ---- */
  snprintf(buf, sizeof(buf), "%-4d", gas);
  tft.setTextSize(3);
  tft.setTextColor(C_YELLOW, C_CARD);
  tft.setCursor(X1 + 10, Y2 + 24);
  tft.print(buf);

  const char* capG = "UDARA BAIK";
  uint16_t colG = C_GREEN;
  if (gas > AMBANG_RAW_GAS)          { capG = "BERBAHAYA!"; colG = C_RED;    }
  else if (gas > AMBANG_RAW_GAS / 2) { capG = "SEDANG";     colG = C_YELLOW; }
  drawBar(2, X1 + 10, Y2 + 52, CW - 20, 6, gas, 0, 1023, colG);
  drawCaption(X1 + 10, Y2 + 64, capG, colG);
}

/* ======================== ALASAN WARNING & STATUS ==================== */
String buildReason() {
  String r = "";
  int n = 0;
  if (gas > AMBANG_RAW_GAS)    { if (n == 0) r = "Gas terlalu tinggi";   n++; }
  if (ppm_asap > AMBANG_ASAP)  { if (n == 0) r = "Asap terdeteksi";      n++; }
  if (suhu > AMBANG_SUHU)      { if (n == 0) r = "Suhu terlalu tinggi";  n++; }
  if (suhu < SUHU_MIN)         { if (n == 0) r = "Suhu terlalu rendah";  n++; }
  if (hum < LEMBAB_MIN)        { if (n == 0) r = "Udara terlalu kering"; n++; }
  if (hum > LEMBAB_MAX)        { if (n == 0) r = "Udara terlalu lembab"; n++; }
  if (n == 0) return "Semua parameter aman";
  if (n > 1) r += " +" + String(n - 1);
  return r;
}

void updateReason() {
  String r = buildReason();
  if (r == prevReason) return;
  prevReason = r;
  char buf[24];
  snprintf(buf, sizeof(buf), "%-21.21s", r.c_str());
  tft.setTextSize(1);
  tft.setTextColor(isWarning ? C_YELLOW : C_MUTED, C_CARD);
  tft.setCursor(X2 + 10, Y2 + 62);
  tft.print(buf);
}

void updateStatus(bool blinkOn) {
  int mode = !isWarning ? 0 : (blinkOn ? 1 : 2);
  if (mode == prevMode) return;
  prevMode = mode;

  const int px = X2 + 10, py = Y2 + 22, pw = CW - 20, ph = 32;

  tft.setTextSize(2);
  if (mode == 0) {                                   // NORMAL
    tft.fillRoundRect(px, py, pw, ph, 8, C_GREEN);
    tft.setTextColor(C_BG);
    tft.setCursor(px + (pw - 6 * 12) / 2, py + 9);
    tft.print("NORMAL");
  } else if (mode == 1) {                            // WARNING (terang)
    tft.fillRoundRect(px, py, pw, ph, 8, C_RED);
    tft.setTextColor(C_TEXT);
    tft.setCursor(px + (pw - 7 * 12) / 2, py + 9);
    tft.print("WARNING");
  } else {                                           // WARNING (redup)
    tft.fillRoundRect(px, py, pw, ph, 8, C_CARD);
    tft.drawRoundRect(px, py, pw, ph, 8, C_RED);
    tft.drawRoundRect(px + 1, py + 1, pw - 2, ph - 2, 7, C_RED);
    tft.setTextColor(C_RED);
    tft.setCursor(px + (pw - 7 * 12) / 2, py + 9);
    tft.print("WARNING");
  }
}

/* ================================ FOOTER ============================= */
void updateFooter() {
  char buf[56];
  char isi[56];
  tft.setTextSize(1);
  tft.setTextColor(C_TEXT, C_CARD);
  tft.setCursor(12, 212);
  snprintf(isi, sizeof(isi), "CO2 %.0f NH3 %.0f BNZ %.0f ALK %.0f ASP %.0f",
           constrain(ppm_co2, 0, 99999),
           constrain(ppm_amonia, 0, 99999),
           constrain(ppm_benzena, 0, 99999),
           constrain(ppm_alkohol, 0, 99999),
           constrain(ppm_asap, 0, 99999));
  snprintf(buf, sizeof(buf), "%-49.49s", isi);
  tft.print(buf);

  if (WiFi.status() == WL_CONNECTED) {
    String s = WiFi.localIP().toString() + "   HTTP " + String(lastHttp);
    setFooterLine2(s, (lastHttp == 200) ? C_GREEN : C_YELLOW);
  } else {
    setFooterLine2("WiFi OFFLINE - data tidak terkirim", C_RED);
  }
}

void setFooterLine2(String s, uint16_t col) {
  char buf[56];
  snprintf(buf, sizeof(buf), "%-48.48s", s.c_str());
  tft.setTextSize(1);
  tft.setTextColor(col, C_CARD);
  tft.setCursor(12, 223);
  tft.print(buf);
}