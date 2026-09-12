# MEMORY & SPESIFIKASI PROYEK: INVENTORY & GUDANG PABRIK

## 📌 Deskripsi Proyek
Sistem Manajemen Stok & Inventory Gudang Pabrik Berbasis Web Base & Mobile APK Android (MaoneArt) dengan AI Assistant (Tanya Si-nya) dan ekspor PDF/Excel.

## 🛠️ Stack Teknologi
- **Backend Web**: PHP Native 8.x
- **Database**: MariaDB / MySQL (`db_inventory.sql`)
- **Frontend**: HTML5, Tailwind CSS, Glassmorphic Design, Bootstrap Icons CDN
- **Modal System**: MaoneArt Glassmorphism Modal (`showConfirmModal` / `showAlertModal`) dengan 2-kolom grid simetris (`grid grid-cols-2 gap-3`)
- **AI Engine**: Google Gemini API via Secure Client-side Storage (Manual input token)
- **Mobile APK**: Android Native WebView (Java + Gradle) & GitHub Actions Auto Build

## 📂 Struktur Utama
- `index.php` : Dashboard monitoring aktual stok realtime
- `masuk.php` : Input penerimaan barang masuk (Stock In)
- `keluar.php` : Input pengeluaran barang & tools (Stock Out)
- `barang.php` : Master katalog barang & manajemen satuan lengkap
- `supplier.php` : Master data rekanan supplier
- `pic.php` : Master data karyawan/PIC peminta barang
- `laporan.php` : Rekapitulasi mutasi arus barang
- `export.php` : Generator Excel & Dokumen PDF cetak
- `tanya_ai.php` : Antarmuka chat AI "Tanya Si-nya"
- `api/ai_assistant.php` : Handler backend AI logistik
- `app.php` : Antarmuka khusus petugas lapangan mode mobile/APK
- `android/` : Source code Android Studio / Gradle untuk APK
- `.github/workflows/build_apk.yml` : GitHub Actions Auto-Compile APK
