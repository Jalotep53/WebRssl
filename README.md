# WebBaru - Tiruan Web SIMRS Legacy

WebBaru adalah fondasi migrasi aplikasi desktop SIMRS (`D:\WORK\khanza\Khanza_rssl`) ke bentuk web dengan pendekatan:

1. scan struktur dan alur aplikasi legacy,
2. petakan menu + hak akses + konfigurasi,
3. bangun modul web inti bertahap.

## Fitur yang sudah tersedia

- Dashboard ringkasan hasil scan legacy.
- Halaman hasil scan keseluruhan aplikasi.
- Katalog menu hasil ekstraksi otomatis dari `frmUtama.java`.
- Halaman klasifikasi konfigurasi dari `setting/database.xml`.
- Modul inti web:
  - Registrasi
  - Rawat Jalan
  - Farmasi
  - Billing Rawat Jalan (dengan pemisahan Obat/BMHP/Gas Medis)
  - Laporan (listing report template)

## Struktur penting

- `public/index.php` router web
- `src/Controllers/*` controller per halaman/modul
- `src/Services/LegacyScanService.php` baca hasil scan legacy
- `src/Services/SimrsQueryService.php` query DB aman
- `src/Views/*` tampilan
- `config/database.php` koneksi DB
- `docs_menu_access.json` hasil mapping permission->menu
- `docs_config_keys.json` daftar key konfigurasi
- `docs_packages.json` statistik package Java
- `tools/scan_legacy.ps1` script refresh scan

## Menjalankan

1. Atur kredensial DB di `config/database.php`.
2. Jalankan:
   - `php -S 127.0.0.1:8080 -t public`
3. Buka:
   - `http://127.0.0.1:8080`

## Refresh hasil scan legacy

Jalankan:

- `powershell -ExecutionPolicy Bypass -File tools/scan_legacy.ps1`

## Catatan kategori farmasi

- Kategori sumber: `obat_bmhp_oksigen.kode_kat`
- Mapping:
  - `1 = Obat`
  - `2 = BMHP`
  - `3 = Gas Medis`
