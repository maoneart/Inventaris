# 📦 MaoneArt Inventory & Gudang Pabrik (Warehouse Management System)

Sistem Informasi Manajemen Stok & Inventory Pergudangan Berbasis **Web Base Realtime** & **Aplikasi Mobile (APK Android)** yang dilengkapi fitur asisten pintar **🤖 Tanya Si-nya (AI Logistics Assistant)** serta Ekspor Dokumen Resmi (PDF & Excel).

Diciptakan oleh **Hermawan (MaoneArt)** untuk operasional pabrik, perusahaan & pergudangan modern.

---

## 🌟 Fitur Utama

1. **Web Base Dashboard (Pusat Kontrol Realtime)**:
   - Monitoring stok fisik secara realtime (Stok Awal, Masuk, Keluar, Sisa Stok).
   - Indikator status stok pintar (*Aman*, *Menipis/Kritis*, *Habis*).
   - Filter pencarian cepat nama barang, kode SKU, dan lokasi rak simpan.

2. **Mobile APK Petugas Gudang (`app.php`)**:
   - Tampilan sentuh nyaman jempol (*Thumb-friendly big buttons*) untuk petugas lapangan.
   - **Input Barang Masuk (Stock In)**: Pilih Supplier, nomor surat jalan (SJ/PO), multi-item sekaligus.
   - **Input Barang Keluar (Stock Out)**: Pilih PIC Pengambil, divisi kerja, jenis pemakaian (habis pakai / pinjam tools), proteksi batas stok fisik.

3. **🤖 Fitur "Tanya Si-nya" (AI Assistant Gudang)**:
   - Terhubung langsung dengan database inventory realtime.
   - Cek stok dengan percakapan bahasa manusia: *"Berapa sisa baut M10 dan oli Tellus sekarang?"*.
   - Analisa restock otomatis: *"Barang apa saja yang stoknya sudah kritis?"*.
   - Minta draf laporan instan dan tautan sekali klik untuk unduh **Excel (.xls)** dan cetak **PDF**.
   - **Keamanan 100% Terjamin**: Token Gemini diinput manual oleh pengguna dari tampilan web dan disimpan di browser lokal (LocalStorage), tidak pernah disimpan di kodingan publik.

4. **Pilihan Satuan Lengkap & Fleksibel**:
   - Unit (`pcs`, `unit`, `buah`, `set`, `pasang`, `pack`).
   - Berat (`kg`, `gram`, `ton`, `ons`).
   - Volume (`liter`, `ml`, `drum`, `galon`, `can`).
   - Panjang/Dimensi (`meter`, `cm`, `mm`, `roll`, `lembar`, `batang`).
   - Kemasan (`box`, `dus`, `sak`, `karung`, `tube`, `botol`, `kaleng`).
   - Bisa tambah custom satuan baru kapan saja.

5. **Ekspor & Cetak Dokumen Resmi (`export.php`)**:
   - Ekspor Excel (.xls) siap olah pembukuan.
   - Cetak PDF Resmi siap tanda tangan *Petugas Gudang* & *Mengetahui Supervisor*.

---

## 🚀 Setup & Instalasi di Kantor / Pabrik

### 1. Di Komputer Kantor (Server Pusat)
- Pasang XAMPP / Laragon di Windows.
- Salin folder `Inventory` ke `C:\xampp\htdocs\`.
- Buka phpMyAdmin (`http://localhost/phpmyadmin`), buat database `db_inventory`, lalu impor `db_inventory.sql`.
- Buka dashboard di `http://localhost/Inventory/`.

### 2. Di HP Petugas Lapangan (APK / PWA)
- Hubungkan HP ke **WiFi kantor yang sama** dengan komputer kantor.
- Buka browser ke `http://<IP_KOMPUTER_KANTOR>/Inventory/app.php`.
- Atau download file APK `MaoneArt-Gudang-Release.apk` dari menu Releases di GitHub ini!

---

## 📱 Otomasi Build APK via GitHub Actions
Setiap commit atau update ke branch `main`, GitHub Actions otomatis meng-compile aplikasi Android dan menerbitkan file APK siap instal di tab **Releases / Artifacts**.
