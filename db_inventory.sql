-- Database: db_inventory
-- Sistem Manajemen Stok & Inventory Barang (MaoneArt)
-- Kompatibel dengan MariaDB Termux, MySQL XAMPP, Laragon, & Server Kantor

CREATE DATABASE IF NOT EXISTS db_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_inventory;

-- 1. Tabel Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'petugas') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Tabel Satuan (Pilihan Super Lengkap & Fleksibel)
CREATE TABLE IF NOT EXISTS satuan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_satuan VARCHAR(50) NOT NULL,
    singkatan VARCHAR(20) NOT NULL,
    kategori ENUM('unit', 'berat', 'volume', 'panjang', 'kemasan', 'lainnya') DEFAULT 'unit',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Tabel Kategori Barang
CREATE TABLE IF NOT EXISTS kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. Tabel Master Data Supplier (Asal Pemasukan Barang)
CREATE TABLE IF NOT EXISTS supplier (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_supplier VARCHAR(30) UNIQUE,
    nama_supplier VARCHAR(150) NOT NULL,
    kontak_person VARCHAR(100),
    no_telp VARCHAR(50),
    email VARCHAR(100),
    alamat TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 5. Tabel Master Data PIC (Karyawan / Teknisi Pengambil Barang)
CREATE TABLE IF NOT EXISTS pic (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nip_nik VARCHAR(50),
    nama_pic VARCHAR(100) NOT NULL,
    departemen VARCHAR(100) NOT NULL,
    jabatan VARCHAR(100),
    no_hp VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 6. Tabel Master Data Barang & Aktual Stok
CREATE TABLE IF NOT EXISTS barang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_barang VARCHAR(50) NOT NULL UNIQUE,
    barcode VARCHAR(100),
    part_number VARCHAR(100),
    nama_barang VARCHAR(200) NOT NULL,
    id_kategori INT NOT NULL,
    id_supplier INT,
    id_satuan INT NOT NULL,
    stok_saat_ini DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    stok_minimum DECIMAL(12,2) NOT NULL DEFAULT 5.00,
    lokasi_rak VARCHAR(100) DEFAULT 'Rak Umum',
    spesifikasi TEXT,
    foto VARCHAR(255) DEFAULT 'default_item.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kategori) REFERENCES kategori(id) ON DELETE RESTRICT,
    FOREIGN KEY (id_supplier) REFERENCES supplier(id) ON DELETE SET NULL,
    FOREIGN KEY (id_satuan) REFERENCES satuan(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 7. Tabel Transaksi Barang Masuk (Stock In)
CREATE TABLE IF NOT EXISTS transaksi_masuk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_masuk VARCHAR(50) NOT NULL UNIQUE,
    no_surat_jalan_po VARCHAR(100),
    id_supplier INT NOT NULL,
    tanggal_masuk DATE NOT NULL,
    total_item INT NOT NULL DEFAULT 0,
    total_qty DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    catatan TEXT,
    id_user INT,
    status_sync TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_supplier) REFERENCES supplier(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 8. Tabel Detail Transaksi Barang Masuk
CREATE TABLE IF NOT EXISTS detail_transaksi_masuk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_transaksi_masuk INT NOT NULL,
    id_barang INT NOT NULL,
    qty DECIMAL(12,2) NOT NULL,
    id_satuan INT NOT NULL,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_transaksi_masuk) REFERENCES transaksi_masuk(id) ON DELETE CASCADE,
    FOREIGN KEY (id_barang) REFERENCES barang(id) ON DELETE RESTRICT,
    FOREIGN KEY (id_satuan) REFERENCES satuan(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 9. Tabel Transaksi Barang Keluar (Stock Out)
CREATE TABLE IF NOT EXISTS transaksi_keluar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_keluar VARCHAR(50) NOT NULL UNIQUE,
    id_pic INT NOT NULL,
    keperluan VARCHAR(255) NOT NULL,
    jenis_pengeluaran ENUM('habis_pakai', 'peminjaman_tools') NOT NULL DEFAULT 'habis_pakai',
    tanggal_keluar DATE NOT NULL,
    total_item INT NOT NULL DEFAULT 0,
    total_qty DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    catatan TEXT,
    id_user INT,
    status_sync TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pic) REFERENCES pic(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 10. Tabel Detail Transaksi Barang Keluar
CREATE TABLE IF NOT EXISTS detail_transaksi_keluar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_transaksi_keluar INT NOT NULL,
    id_barang INT NOT NULL,
    qty DECIMAL(12,2) NOT NULL,
    id_satuan INT NOT NULL,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_transaksi_keluar) REFERENCES transaksi_keluar(id) ON DELETE CASCADE,
    FOREIGN KEY (id_barang) REFERENCES barang(id) ON DELETE RESTRICT,
    FOREIGN KEY (id_satuan) REFERENCES satuan(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 11. Tabel Pengaturan Sistem & AI
CREATE TABLE IF NOT EXISTS app_settings (
    key_name VARCHAR(100) PRIMARY KEY,
    key_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- SEED DATA AWAL:
-- Admin & Petugas (password default: admin123 & petugas123)
INSERT INTO users (username, password, nama_lengkap, role) VALUES
('admin', '$2y$10$tQO4N826oQO1U4R8fFvI5O5WzP/6C3z6JmGfD4R7nJ1gL7tQO4N82', 'Administrator Gudang', 'admin'),
('petugas', '$2y$10$tQO4N826oQO1U4R8fFvI5O5WzP/6C3z6JmGfD4R7nJ1gL7tQO4N82', 'Petugas Lapangan', 'petugas')
ON DUPLICATE KEY UPDATE id=id;

-- Seed Pilihan Satuan Beragam (Unit, Berat, Volume, Panjang, Kemasan)
INSERT INTO satuan (nama_satuan, singkatan, kategori) VALUES
('Pieces', 'pcs', 'unit'),
('Unit', 'unit', 'unit'),
('Buah', 'buah', 'unit'),
('Set', 'set', 'unit'),
('Pasang', 'psg', 'unit'),
('Pack / Bungkus', 'pack', 'kemasan'),
('Kilogram', 'kg', 'berat'),
('Gram', 'g', 'berat'),
('Ton', 'ton', 'berat'),
('Ons', 'ons', 'berat'),
('Miligram', 'mg', 'berat'),
('Liter', 'L', 'volume'),
('Mililiter', 'ml', 'volume'),
('Drum / Tong', 'drum', 'volume'),
('Galon', 'galon', 'volume'),
('Kaleng / Can', 'can', 'kemasan'),
('Meter', 'm', 'panjang'),
('Centimeter', 'cm', 'panjang'),
('Milimeter', 'mm', 'panjang'),
('Roll / Gulung', 'roll', 'panjang'),
('Lembar / Sheet', 'lbr', 'panjang'),
('Batang', 'btg', 'panjang'),
('Box / Kotak', 'box', 'kemasan'),
('Dus / Karton', 'dus', 'kemasan'),
('Sak / Zak', 'sak', 'kemasan'),
('Karung', 'krg', 'kemasan'),
('Tube / Odol', 'tube', 'kemasan'),
('Botol', 'btl', 'kemasan')
ON DUPLICATE KEY UPDATE id=id;

-- Seed Kategori
INSERT INTO kategori (nama_kategori, deskripsi) VALUES
('Tools & Perkakas', 'Peralatan mekanik, mesin tangan, obeng, kunci pas, bor, dll'),
('Material Habis Pakai', 'Barang operasional yang habis sekali pakai (kawat las, amplas, lakban, dll)'),
('Sparepart & Komponen', 'Suku cadang mesin, bearing, filter, rantai, vanbelt'),
('Bahan Baku (Raw Material)', 'Plat besi, pipa baja, aluminium, besi beton, balok'),
('Safety & APD', 'Helm proyek, sarung tangan safety, kacamata las, sepatu safety'),
('Kimia & Pelumas', 'Oli hidrolik, grease/gemuk, thinner, pelarut, cat'),
('ATK & Dokumen', 'Kertas, spidol permanen, map order, label barcode')
ON DUPLICATE KEY UPDATE id=id;

-- Seed Supplier Contoh
INSERT INTO supplier (kode_supplier, nama_supplier, kontak_person, no_telp, email, alamat) VALUES
('SUP-001', 'PT Mandiri Perkasa Teknik', 'Hendra Gunawan', '0812-3456-7890', 'sales@mandiriteknik.co.id', 'Kawasan Industri Jababeka Blok C12, Cikarang'),
('SUP-002', 'CV Sumber Makmur Abadi', 'Bambang Sudiro', '0813-9876-5432', 'sumbermakmur@gmail.com', 'Jl. Raya Narogong KM 14, Bekasi'),
('SUP-003', 'PT Cahaya Pelumas Nusantara', 'Dewi Lestari', '0811-2233-4455', 'order@cahayapelumas.com', 'Jl. Industri Daan Mogot No. 88, Jakarta Barat'),
('SUP-004', 'Toko Sentosa Baut & Mur', 'Koh Alim', '0856-7788-9900', 'sentosabaut@yahoo.com', 'Pertokoan Lindeteves Trade Center (LTC) Glodok')
ON DUPLICATE KEY UPDATE id=id;

-- Seed PIC Pengambil Contoh (Teknisi / Divisi)
INSERT INTO pic (nip_nik, nama_pic, departemen, jabatan, no_hp) VALUES
('PIC-101', 'Budi Santoso', 'Maintenance', 'Senior Teknisi', '0812-9988-1122'),
('PIC-102', 'Ahmad Fauzi', 'Produksi Line 1', 'Operator Mesin', '0813-8877-2233'),
('PIC-103', 'Doni Wijaya', 'Fabrikasi & Las', 'Welder Specialist', '0857-7766-3344'),
('PIC-104', 'Rian Hidayat', 'Utility & Kelistrikan', 'Electrician', '0819-6655-4455'),
('PIC-105', 'Siti Rahma', 'Quality Control', 'QC Inspector', '0821-5544-5566')
ON DUPLICATE KEY UPDATE id=id;

-- Seed Barang Awal Contoh
INSERT INTO barang (kode_barang, barcode, nama_barang, id_kategori, id_satuan, stok_saat_ini, stok_minimum, lokasi_rak, spesifikasi) VALUES
('BRG-001', '8991001001', 'Kunci Pas Ring Set 8-24mm', 1, 4, 12.00, 3.00, 'Rak A-01 (Tools)', 'Bahan Chrome Vanadium 14 pcs per set'),
('BRG-002', '8991001002', 'Mesin Gerinda Tangan 4 Inch Bosch', 1, 2, 6.00, 2.00, 'Rak A-02 (Power Tools)', '720 Watt, 11.000 RPM, GWS 060'),
('BRG-003', '8991001003', 'Kawat Las RD-260 3.2mm', 2, 7, 45.50, 15.00, 'Rak B-01 (Welding)', 'Nippon Steel E6013 diameter 3.2mm'),
('BRG-004', '8991001004', 'Baut & Mur Hex M10 x 30mm Baja', 3, 1, 350.00, 100.00, 'Bin C-05 (Fastener)', 'Baja Grade 8.8 Galvanized'),
('BRG-005', '8991001005', 'Oli Hidrolik Tellus S2 M 68', 6, 14, 3.00, 1.00, 'Area Drum Belakang', 'Shell Tellus drum isi 209 Liter'),
('BRG-006', '8991001006', 'Sarung Tangan Las Kulit 14 Inch', 5, 5, 28.00, 10.00, 'Rak D-01 (Safety)', 'Kulit Split tebal tahan percikan panas'),
('BRG-007', '8991001007', 'Plat Besi Hitam Tebal 3mm (4x8 ft)', 4, 21, 18.00, 5.00, 'Lantai Rak Besi Raw', 'Mild Steel Sheet SPCC 1.2m x 2.4m'),
('BRG-008', '8991001008', 'Grease / Gemuk Pelumas EP2 Chassis', 6, 16, 14.00, 5.00, 'Rak B-03 (Pelumas)', 'Chassis Grease Rotary Can 500g')
ON DUPLICATE KEY UPDATE id=id;

-- Seed App Settings
INSERT INTO app_settings (key_name, key_value) VALUES
('nama_aplikasi', 'MaoneArt Stock & Inventory System'),
('nama_gudang', 'Gudang Pusat & Workshop Logistik'),
('nama_kantor', 'PT MaoneArt Teknologi Presisi'),
('alamat_kantor', 'Kawasan Industri Mandiri, Jl. Wijaya Kusuma No. 88'),
('telepon_kantor', '(021) 8899-7722'),
('gemini_api_key', ''),
('ai_provider', 'gemini'),
('local_router_url', 'http://127.0.0.1:20128/v1')
ON DUPLICATE KEY UPDATE updated_at=CURRENT_TIMESTAMP;
