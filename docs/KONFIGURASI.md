# Konfigurasi Legacy yang Dipetakan

Sumber utama:

- `setting/database.xml`
- `src/fungsi/koneksiDB.java`

## Kelompok konfigurasi

1. Database koneksi utama (`HOST`, `PORT`, `DATABASE`, `USER`, `PAS`)
2. Hybrid web/antrian (`HOSTHYBRIDWEB`, `PORTWEB`, `HYBRIDWEB`, `ANTRIAN`)
3. Alarm operasional (`ALARMAPOTEK`, `ALARMLAB`, `ALARMRADIOLOGI`, dst)
4. Bridging API (BPJS, PCare, Aplicare, Inhealth, SatuSehat, SIRS)
5. Pengaturan farmasi/billing (`AKTIFKANBILLINGPARSIAL`, `CETAKRINCIANOBAT`, dst)
6. Integrasi sistem lain (LIS/RIS/Orthanc, Dukcapil, host-to-host bank)

Nilai konfigurasi sensitif tidak ditampilkan di web versi ini.

