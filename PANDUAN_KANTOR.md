# 🏢 Panduan Setup & Migrasi Sistem Gudang ke Komputer Kantor

Aplikasi ini dirancang menggunakan arsitektur **Client-Server LAN WiFi Kantor**:
- **1 Komputer Kantor** bertindak sebagai Pusat Server (Web Base Dashboard, Realtime Stock, & Database).
- **HP / Tablet Petugas Lapangan** menginstal/membuka APK untuk input Barang Masuk & Barang Keluar secara realtime.

---

## 🚀 Langkah 1: Pasang di Komputer Kantor (Hanya Sekali)
1. Pasang web server di Komputer Kantor (disarankan menggunakan **XAMPP** atau **Laragon** di Windows).
2. Copy folder `Inventaris` dari HP ini (`/sdcard/www/Inventaris`) ke folder `C:\xampp\htdocs\` di komputer kantor.
3. Buka phpMyAdmin di komputer kantor (`http://localhost/phpmyadmin`):
   - Buat database baru bernama `db_inventaris`.
   - Klik **Import** -> Pilih file `db_inventaris.sql`.
4. Buka file `config/database.php` di komputer kantor:
   - Pastikan user: `'root'` dan password: `''` (kosong jika standar XAMPP).

---

## 📶 Langkah 2: Cara Menghubungkan HP Petugas ke Komputer Kantor via WiFi
1. Pastikan Komputer Kantor dan HP Petugas terhubung ke **WiFi Kantor yang sama**.
2. Cek IP Komputer Kantor:
   - Buka CMD di komputer kantor, ketik `ipconfig`.
   - Lihat bagian *IPv4 Address*, misalnya: `192.168.1.50`.
3. Buka browser di HP Petugas (Chrome / Edge):
   - Buka alamat: `http://192.168.1.50/Inventaris/app.php`
4. **Jadikan APK di HP**:
   - Di browser Chrome HP, tekan menu titik tiga (⋮) di pojok kanan atas.
   - Pilih **"Tambahkan ke Layar Utama" (Add to Home Screen)** atau **"Instal Aplikasi"**.
   - Ikon aplikasi gudang akan muncul di beranda HP persis seperti aplikasi APK biasa!

---

## 📦 Alur Kerja Sehari-hari di Kantor:
1. **Petugas di Pintu Masuk / Gudang (Via HP)**:
   - Begitu ada kiriman truk/supplier datang, buka APK -> Klik **INPUT BARANG MASUK**.
   - Pilih supplier, no surat jalan, pilih barang & jumlah -> Klik Simpan.
   - Stok di komputer kantor otomatis bertambah detik itu juga.

2. **Petugas Pengeluaran Tools / Sparepart (Via HP)**:
   - Teknisi/PIC minta alat atau kawat las -> Buka APK -> Klik **INPUT BARANG KELUAR**.
   - Pilih nama PIC & divisinya, jumlah barang -> Klik Simpan.
   - Stok gudang langsung otomatis berkurang.

3. **Admin / Pimpinan di Meja Kantor (Via Layar PC / Laptop)**:
   - Buka `http://localhost/Inventaris/index.php`.
   - Memantau stok realtime tanpa perlu tanya manual ke petugas gudang.
   - Klik menu **Laporan** untuk download Excel / cetak PDF.
   - Gunakan **Tanya Si-nya (AI)** untuk bertanya data stok atau meminta ringkasan laporan otomatis.
