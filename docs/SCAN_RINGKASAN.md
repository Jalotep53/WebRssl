# Scan Ringkasan SIMRS Legacy

Sumber scan: `D:\WORK\khanza\Khanza_rssl`

## Hasil utama

- Entry aplikasi desktop: `src/simrskhanza/SIMRSKhanza.java`
- Main window/menu: `src/simrskhanza/frmUtama.java`
- Mapping menu-hak akses hasil ekstraksi: `../docs_menu_access.json`
- Total menu terpetakan: 1123
- Total komponen tombol berlabel: 1165
- Key konfigurasi `setting/database.xml`: 159

## Struktur modul (berdasarkan package Java)

1. bridging
2. rekammedis
3. keuangan
4. grafikanalisa
5. inventory
6. laporan
7. kepegawaian
8. simrskhanza
9. surat
10. ipsrs

## Alur bisnis besar (ringkas)

1. Registrasi pasien (rawat jalan/rawat inap, poli, penjamin, booking/antrian)
2. Pelayanan klinis (tindakan, laborat, radiologi, operasi, rekam medis)
3. Farmasi & logistik medis (resep, pemberian obat, stok, pengadaan, retur)
4. Billing & keuangan (tagihan, kasir, piutang, jurnal, rekening)
5. Pelaporan & analitik (rekap, RL, grafik kunjungan, dashboard mutu)
6. Bridging eksternal (BPJS VClaim/PCare/Aplicare, SatuSehat, SIRS, dll)

