<?php
// pengaturan.php - Pengaturan Sistem, Server Kantor, Token AI & Profil Gudang
$pageTitle = "Pengaturan Sistem";
require_once __DIR__ . '/config/database.php';

// Proses Simpan Pengaturan Profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'simpan_profil') {
        $nama_aplikasi  = trim($_POST['nama_aplikasi'] ?? 'MaoneArt Stock & Inventory');
        $nama_gudang    = trim($_POST['nama_gudang'] ?? 'Gudang Pusat & Logistik');
        $nama_kantor    = trim($_POST['nama_kantor'] ?? 'PT MaoneArt Teknologi Presisi');
        $telepon_kantor = trim($_POST['telepon_kantor'] ?? '');
        $alamat_kantor  = trim($_POST['alamat_kantor'] ?? '');

        $settings = [
            'nama_aplikasi'  => $nama_aplikasi,
            'nama_gudang'    => $nama_gudang,
            'nama_kantor'    => $nama_kantor,
            'telepon_kantor' => $telepon_kantor,
            'alamat_kantor'  => $alamat_kantor,
        ];

        try {
            $stmt = $pdo->prepare("
                INSERT INTO app_settings (key_name, key_value, updated_at) 
                VALUES (?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE key_value = VALUES(key_value), updated_at = NOW()
            ");

            foreach ($settings as $k => $v) {
                $stmt->execute([$k, $v]);
            }

            setFlash('success', 'Pengaturan Disimpan', 'Identitas gudang dan profil perusahaan berhasil diperbarui.');
        } catch (Exception $e) {
            setFlash('danger', 'Gagal Menyimpan', $e->getMessage());
        }

        header('Location: pengaturan.php');
        exit;
    }

    // Reset Data Transaksi (Maintenance)
    if ($_POST['action'] === 'reset_transaksi') {
        try {
            $pdo->beginTransaction();
            $pdo->exec("DELETE FROM detail_transaksi_masuk");
            $pdo->exec("DELETE FROM transaksi_masuk");
            $pdo->exec("DELETE FROM detail_transaksi_keluar");
            $pdo->exec("DELETE FROM transaksi_keluar");
            $pdo->exec("UPDATE barang SET stok_saat_ini = 0");
            $pdo->commit();

            setFlash('success', 'Reset Berhasil', 'Seluruh riwayat transaksi masuk/keluar berhasil dibersihkan dan stok disetel ke 0.');
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('danger', 'Gagal Reset Transaksi', $e->getMessage());
        }

        header('Location: pengaturan.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';

// Ambil nilai pengaturan terkini
$currNamaAplikasi = getSetting('nama_aplikasi', 'MaoneArt Stock & Inventory');
$currNamaGudang   = getSetting('nama_gudang', 'Gudang Pusat & Workshop Logistik');
$currNamaKantor   = getSetting('nama_kantor', 'PT MaoneArt Teknologi Presisi');
$currTelepon      = getSetting('telepon_kantor', '(021) 8899-7722');
$currAlamat       = getSetting('alamat_kantor', 'Kawasan Industri Mandiri, Jl. Wijaya Kusuma No. 88');

// Info Server & Jaringan
$serverHost = $_SERVER['HTTP_HOST'] ?? 'localhost:8085';
?>

<!-- iOS Minimalist Header Ala iPhone (Exact Screenshot) -->
<div class="ios-top-bar">
  <a href="index.php" class="ios-circle-back" title="Kembali ke Dashboard">
    <i class="bi bi-chevron-left"></i>
  </a>
  <h1 class="ios-bar-title">Pengaturan</h1>
  <div class="ios-bar-action"></div>
</div>

<!-- Group 0: Tema Tampilan Sistem (Light & Dark Mode) -->
<div class="ios-form-card">
  <div class="ios-group-title">
    <i class="bi bi-palette-fill"></i> TEMA TAMPILAN SISTEM (THEME MODE)
  </div>

  <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 14px;">
    Pilih gaya tampilan visual sistem. Seluruh warna kartu, form input, tabel, navigasi bar, dan latar belakang akan menyesuaikan secara instan.
  </p>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px;">
    <!-- Dark Mode Card -->
    <div id="themeCardDark" class="theme-option-card active" onclick="setAppTheme('dark')" style="background: rgba(15, 23, 42, 0.85);">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
        <div style="display: flex; align-items: center; gap: 10px;">
          <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(30, 41, 59, 0.9); display: flex; align-items: center; justify-content: center; font-size: 1.15rem; color: #60a5fa;">
            <i class="bi bi-moon-stars-fill"></i>
          </div>
          <div>
            <div style="font-weight: 800; font-size: 0.95rem; color: #ffffff;">Mode Gelap (Dark)</div>
            <div style="font-size: 0.72rem; color: #94a3b8;">Dark Navy Industrial</div>
          </div>
        </div>
        <i class="bi bi-check-circle-fill check-icon" style="font-size: 1.25rem; color: #2563eb;"></i>
      </div>
      <div style="height: 38px; border-radius: 10px; background: #0b0f19; border: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; padding: 0 12px; gap: 8px;">
        <div style="width: 16px; height: 7px; border-radius: 99px; background: #2563eb;"></div>
        <div style="width: 45px; height: 7px; border-radius: 99px; background: rgba(255,255,255,0.25);"></div>
        <div style="width: 25px; height: 7px; border-radius: 99px; background: rgba(16,185,129,0.4); margin-left: auto;"></div>
      </div>
    </div>

    <!-- Light Mode Card -->
    <div id="themeCardLight" class="theme-option-card" onclick="setAppTheme('light')" style="background: rgba(255, 255, 255, 0.95);">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
        <div style="display: flex; align-items: center; gap: 10px;">
          <div style="width: 36px; height: 36px; border-radius: 10px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; color: #f59e0b;">
            <i class="bi bi-sun-fill"></i>
          </div>
          <div>
            <div style="font-weight: 800; font-size: 0.95rem; color: #0f172a;">Mode Terang (White)</div>
            <div style="font-size: 0.72rem; color: #64748b;">Clean White Modern</div>
          </div>
        </div>
        <i class="bi bi-check-circle-fill check-icon" style="font-size: 1.25rem; color: #2563eb; display: none;"></i>
      </div>
      <div style="height: 38px; border-radius: 10px; background: #f8fafc; border: 1px solid rgba(0,0,0,0.1); display: flex; align-items: center; padding: 0 12px; gap: 8px;">
        <div style="width: 16px; height: 7px; border-radius: 99px; background: #2563eb;"></div>
        <div style="width: 45px; height: 7px; border-radius: 99px; background: rgba(0,0,0,0.2);"></div>
        <div style="width: 25px; height: 7px; border-radius: 99px; background: rgba(16,185,129,0.4); margin-left: auto;"></div>
      </div>
    </div>
  </div>
</div>

<!-- Group 1: Koneksi Server & Jaringan Kantor -->
<div class="ios-form-card">
  <div class="ios-group-title">
    <i class="bi bi-hdd-network-fill"></i> KONEKSI SERVER & JARINGAN KANTOR
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
    <div>
      <label class="ios-label">Status Server Local</label>
      <div class="server-status-box">
        <span style="width: 10px; height: 10px; border-radius: 50%; background: #10b981; display: inline-block; box-shadow: 0 0 10px #10b981;"></span>
        Online Apache Port 8085
      </div>
    </div>

    <div>
      <label class="ios-label">Alamat Akses Saat Ini</label>
      <input type="text" class="ios-input" value="http://<?= htmlspecialchars($serverHost) ?>/Inventory" readonly>
    </div>

    <div>
      <label class="ios-label">Database MySQL / MariaDB</label>
      <div class="db-status-box">
        <i class="bi bi-database-check text-primary"></i> db_inventory (Port 3306)
      </div>
    </div>
  </div>

  <div class="info-guide-box">
    💡 <strong>Cara Pakai di Komputer & WiFi Kantor:</strong><br>
    1. Pastikan Komputer Kantor dan HP terhubung pada WiFi yang sama.<br>
    2. Cek IP Komputer Kantor (misal: <code>192.168.1.100</code>).<br>
    3. Di HP atau aplikasi APK, ganti Server URL menjadi: <code>http://192.168.1.100:8085/Inventory</code>.<br>
    4. Seluruh mutasi stok otomatis tersinkronisasi realtime ke server kantor!
  </div>
</div>

<!-- Group 2: Token Google Gemini AI (Client-Side Storage) -->
<div class="ios-form-card">
  <div class="ios-group-title">
    <i class="bi bi-robot"></i> KECERDASAN BUATAN (GEMINI AI GUDANG)
  </div>

  <p style="font-size: 0.8rem; color: #94a3b8; line-height: 1.5; margin-bottom: 14px;">
    🔒 <strong>Aman & Terisolasi:</strong> Token AI Gemini hanya disimpan di <code>localStorage</code> browser/HP masing-masing pengguna. Token tidak pernah disimpan di repository GitHub kodingan sehingga aman dari pencurian.
  </p>

  <div>
    <label class="ios-label">API Key Google Gemini</label>
    <div style="display: flex; gap: 8px;">
      <input type="password" id="inputTokenGemini" class="ios-input" placeholder="Masukkan token AIzaSy..." autocomplete="off">
      <button type="button" id="btnToggleToken" class="btn btn-secondary btn-sm" style="border-radius: 12px; padding: 0 16px;" onclick="toggleTokenVisibility()">
        <i class="bi bi-eye" id="iconEye"></i>
      </button>
    </div>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; margin-top: 16px;">
    <button type="button" class="ios-btn-primary ios-btn-blue" onclick="saveGeminiToken()">
      <i class="bi bi-check-lg"></i> Simpan Token ke HP
    </button>
    <button type="button" class="btn btn-secondary btn-sm" onclick="clearGeminiToken()" style="border-radius: 14px; height: 48px; font-weight: 700; color: #f87171;">
      <i class="bi bi-trash"></i> Hapus Token
    </button>
  </div>
</div>

<!-- Group 3: Profil Gudang & Identitas Perusahaan -->
<form action="pengaturan.php" method="POST" id="formProfil">
  <input type="hidden" name="action" value="simpan_profil">

  <div class="ios-form-card">
    <div class="ios-group-title">
      <i class="bi bi-building"></i> PROFIL GUDANG & PERUSAHAAN
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
      <div>
        <label class="ios-label">Nama Aplikasi</label>
        <input type="text" name="nama_aplikasi" class="ios-input" value="<?= htmlspecialchars($currNamaAplikasi) ?>" required>
      </div>

      <div>
        <label class="ios-label">Nama Gudang / Cabang</label>
        <input type="text" name="nama_gudang" class="ios-input" value="<?= htmlspecialchars($currNamaGudang) ?>" required>
      </div>

      <div style="grid-column: span 2;">
        <label class="ios-label">Nama Perusahaan / Organisasi</label>
        <input type="text" name="nama_kantor" class="ios-input" value="<?= htmlspecialchars($currNamaKantor) ?>" required>
      </div>

      <div>
        <label class="ios-label">No. Telepon Kantor</label>
        <input type="text" name="telepon_kantor" class="ios-input" value="<?= htmlspecialchars($currTelepon) ?>">
      </div>

      <div style="grid-column: span 2;">
        <label class="ios-label">Alamat Kantor / Workshop Logistik</label>
        <input type="text" name="alamat_kantor" class="ios-input" value="<?= htmlspecialchars($currAlamat) ?>">
      </div>
    </div>

    <div style="margin-top: 20px;">
      <button type="submit" class="ios-btn-primary ios-btn-green">
        <i class="bi bi-save-fill"></i> Simpan Profil Perusahaan
      </button>
    </div>
  </div>
</form>

<!-- Group 4: Pemeliharaan Database & Maintenance -->
<div class="ios-form-card">
  <div class="ios-group-title">
    <i class="bi bi-shield-shaded"></i> PEMELIHARAAN & DATABASE
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
    <a href="export.php?type=backup_db" class="btn btn-secondary" style="border-radius: 14px; padding: 14px; text-align: left; display: flex; align-items: center; gap: 12px;">
      <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(37, 99, 235, 0.15); color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
        <i class="bi bi-download"></i>
      </div>
      <div>
        <div style="font-weight: 700; color: var(--text-main); font-size: 0.88rem;">Download Backup SQL</div>
        <div style="font-size: 0.72rem; color: var(--text-muted);">Cadangan database db_inventory</div>
      </div>
    </a>

    <a href="http://localhost:8085/phpmyadmin" target="_blank" class="btn btn-secondary" style="border-radius: 14px; padding: 14px; text-align: left; display: flex; align-items: center; gap: 12px;">
      <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(245, 158, 11, 0.15); color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
        <i class="bi bi-database-gear"></i>
      </div>
      <div>
        <div style="font-weight: 700; color: var(--text-main); font-size: 0.88rem;">Buka phpMyAdmin</div>
        <div style="font-size: 0.72rem; color: var(--text-muted);">Port 8085 / phpmyadmin</div>
      </div>
    </a>
  </div>

  <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--card-border);">
    <div style="font-size: 0.82rem; font-weight: 700; color: var(--danger); margin-bottom: 6px;">Zona Berbahaya (Maintenance)</div>
    <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 12px;">Hapus seluruh transaksi masuk & keluar jika Anda ingin memulai pencatatan stok dari nol.</p>
    <button type="button" class="btn btn-danger btn-sm" onclick="confirmResetTrans()" style="border-radius: 12px; padding: 10px 18px; font-weight: 700;">
      <i class="bi bi-exclamation-triangle-fill"></i> Bersihkan Riwayat Transaksi Percobaan
    </button>
  </div>
</div>

<!-- Group 5: Tentang Aplikasi (Interactive Action Card) -->
<div class="ios-form-card" style="padding: 18px;">
  <div style="display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: 12px;">
      <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #2563eb, #1d4ed8); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; color: #fff; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35); flex-shrink: 0;">
        <i class="bi bi-info-circle-fill"></i>
      </div>
      <div>
        <div style="font-weight: 800; font-size: 0.95rem; color: var(--text-main);">Tentang Aplikasi</div>
        <div style="font-size: 0.74rem; color: var(--text-muted);">Informasi versi sistem, developer, arsitektur & lisensi resmi</div>
      </div>
    </div>
    <button type="button" class="btn btn-secondary btn-sm" onclick="openAboutModal()" style="border-radius: 12px; padding: 10px 18px; font-weight: 700;">
      <i class="bi bi-eye-fill"></i> Buka Informasi
    </button>
  </div>
</div>

<!-- Modal Full Tentang Aplikasi (MaoneArt Glassmorphism Modal) -->
<div id="modalAboutApp" class="maoneart-modal-overlay" style="display: none;">
  <div class="maoneart-modal-card" style="max-width: 520px; text-align: left; max-height: 85vh; display: flex; flex-direction: column;">
    <!-- Modal Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--card-border); padding-bottom: 12px;">
      <div style="display: flex; align-items: center; gap: 10px;">
        <div style="width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #2563eb, #1d4ed8); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; color: #fff; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);">
          📦
        </div>
        <div>
          <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-main); margin: 0;">Tentang Aplikasi</h3>
          <p style="font-size: 0.72rem; color: #2563eb; margin: 0;">MaoneArt Stock & Inventory System</p>
        </div>
      </div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="closeAboutModal()" style="border-radius: 10px; width: 34px; height: 34px; padding: 0; display: flex; align-items: center; justify-content: center;">✕</button>
    </div>

    <!-- Modal Body (Scrollable) -->
    <div style="flex: 1; overflow-y: auto; padding-right: 4px;">
      <!-- Version Pill & Status -->
      <div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
        <span class="badge badge-primary" style="font-size: 0.72rem; padding: 5px 10px;"><i class="bi bi-tag-fill"></i> Versi 2.0.0 Enterprise</span>
        <span class="badge badge-success" style="font-size: 0.72rem; padding: 5px 10px;"><i class="bi bi-check-circle-fill"></i> Production Ready</span>
        <span class="badge badge-purple" style="font-size: 0.72rem; padding: 5px 10px;"><i class="bi bi-cpu-fill"></i> Gemini AI Ready</span>
      </div>

      <!-- App Overview -->
      <div style="background: var(--input-bg); border: 1px solid var(--card-border); border-radius: 14px; padding: 14px; margin-bottom: 14px; font-size: 0.8rem; color: var(--text-muted); line-height: 1.6;">
        <p style="margin-bottom: 8px;">
          <strong>MaoneArt Stock & Inventory</strong> adalah sistem pergudangan modern berarsitektur <em>Hybrid Local-First</em> yang dirancang untuk kecepatan operasional inventory pabrik, pencatatan Part Number pabrik, nomor surat jalan supplier, dan pengeluaran material ke PIC teknisi secara realtime.
        </p>
        <p style="margin: 0;">
          Dapat berjalan mandiri di smartphone (Android Termux) maupun jaringan WiFi kantor (PC/Server lokal) tanpa ketergantungan hosting berbayar.
        </p>
      </div>

      <!-- Specifications Grid -->
      <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 14px;">
        <div style="background: var(--input-bg); border: 1px solid var(--card-border); border-radius: 12px; padding: 10px;">
          <div style="font-size: 0.68rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Developer / Creator</div>
          <div style="font-size: 0.82rem; font-weight: 800; color: var(--text-main); margin-top: 2px;">Hermawan (MaoneArt)</div>
        </div>
        <div style="background: var(--input-bg); border: 1px solid var(--card-border); border-radius: 12px; padding: 10px;">
          <div style="font-size: 0.68rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Official Portal</div>
          <div style="font-size: 0.82rem; font-weight: 800; color: #2563eb; margin-top: 2px;">
            <a href="https://maoneart.my.id" target="_blank" style="color: #2563eb; text-decoration: none; font-weight: 800;">Maoneart.my.id</a>
          </div>
        </div>
        <div style="background: var(--input-bg); border: 1px solid var(--card-border); border-radius: 12px; padding: 10px;">
          <div style="font-size: 0.68rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Stack Teknologi</div>
          <div style="font-size: 0.82rem; font-weight: 800; color: var(--text-main); margin-top: 2px;">PHP 8.5, MariaDB, Flutter</div>
        </div>
        <div style="background: var(--input-bg); border: 1px solid var(--card-border); border-radius: 12px; padding: 10px;">
          <div style="font-size: 0.68rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Desain Antarmuka</div>
          <div style="font-size: 0.82rem; font-weight: 800; color: var(--text-main); margin-top: 2px;">Apple iOS & Gojek Superapp</div>
        </div>
      </div>

      <!-- Feature Highlights -->
      <div style="font-size: 0.76rem; color: var(--text-muted); line-height: 1.7; margin-bottom: 14px;">
        <div style="font-weight: 800; color: var(--text-main); margin-bottom: 6px;">Fitur Utama Sistem:</div>
        • 📥 <strong>Barang Masuk</strong>: Multi-item supplier, nomor surat jalan / PO, tambah stok otomatis.<br>
        • 📤 <strong>Barang Keluar</strong>: Multi-item PIC teknisi, keperluan proyek, validasi stok minimum.<br>
        • 🏷️ <strong>Katalog Barang</strong>: Manajemen Part Number pabrik, barcode SKU, satuan & lokasi rak.<br>
        • 🤖 <strong>Tanya Si-nya (AI)</strong>: Asisten AI logistik berbasis Gemini API dengan token client-side aman.<br>
        • 📊 <strong>Laporan & Ekspor</strong>: Format cetak resmi PDF dan spreadsheet Excel terstandarisasi.<br>
        • 🌓 <strong>Tema Dinamis</strong>: Mode Terang (Clean White) & Mode Gelap (Dark Navy) instan.
      </div>

      <!-- Copyright Notice -->
      <div style="text-align: center; padding-top: 10px; border-top: 1px solid var(--card-border); font-size: 0.72rem; color: var(--text-muted);">
        © 2026 <strong>MaoneArt</strong> · All Rights Reserved · <a href="https://maoneart.my.id" target="_blank" style="color: #2563eb; text-decoration: none; font-weight: 700;">Maoneart.my.id</a>
      </div>
    </div>

    <!-- Modal Actions (100% Symmetrical 2-Column Grid) -->
    <div class="maoneart-modal-actions" style="margin-top: 16px; border-top: 1px solid var(--card-border); padding-top: 14px;">
      <a href="https://maoneart.my.id" target="_blank" class="maoneart-modal-btn cancel" style="text-decoration: none;">
        <i class="bi bi-globe2"></i> Web Resmi
      </a>
      <button type="button" class="maoneart-modal-btn primary" onclick="closeAboutModal()">
        Tutup
      </button>
    </div>
  </div>
</div>

<!-- Hidden Form for Reset Transaksi -->
<form id="formResetTrans" action="pengaturan.php" method="POST" style="display: none;">
  <input type="hidden" name="action" value="reset_transaksi">
</form>

<script>
// Token Gemini Handling Client-Side
function loadSavedToken() {
  const token = localStorage.getItem('maoneart_gemini_token') || '';
  const inp = document.getElementById('inputTokenGemini');
  if (inp) {
    inp.value = token;
  }
}

function saveGeminiToken() {
  const inp = document.getElementById('inputTokenGemini');
  const val = inp ? inp.value.trim() : '';
  if (!val) {
    showAlertModal({
      title: 'Perhatian',
      message: 'Harap masukkan API Key Google Gemini sebelum menyimpan.',
      type: 'info'
    });
    return;
  }

  localStorage.setItem('maoneart_gemini_token', val);
  showAlertModal({
    title: 'Token Tersimpan!',
    message: 'Token Gemini AI berhasil disimpan di penyimpanan aman browser/HP Anda.',
    type: 'success'
  });
}

function clearGeminiToken() {
  showConfirmModal({
    title: 'Hapus Token AI?',
    message: 'Apakah Anda yakin ingin menghapus token Google Gemini dari browser ini?',
    confirmText: 'Ya, Hapus',
    cancelText: 'Batal',
    type: 'danger',
    onConfirm: () => {
      localStorage.removeItem('maoneart_gemini_token');
      const inp = document.getElementById('inputTokenGemini');
      if (inp) inp.value = '';
      showAlertModal({
        title: 'Terhapus',
        message: 'Token Google Gemini berhasil dihapus.',
        type: 'info'
      });
    }
  });
}

function toggleTokenVisibility() {
  const inp = document.getElementById('inputTokenGemini');
  const icon = document.getElementById('iconEye');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.className = 'bi bi-eye-slash';
  } else {
    inp.type = 'password';
    icon.className = 'bi bi-eye';
  }
}

function confirmResetTrans() {
  showConfirmModal({
    title: 'Konfirmasi Bersihkan Data',
    message: 'Apakah Anda yakin ingin MENGHAPUS SEMUA riwayat transaksi masuk/keluar? Tindakan ini tidak dapat dibatalkan.',
    confirmText: 'Ya, Bersihkan',
    cancelText: 'Batal',
    type: 'danger',
    onConfirm: () => {
      document.getElementById('formResetTrans').submit();
    }
  });
}

function openAboutModal() {
  const m = document.getElementById('modalAboutApp');
  if (m) {
    m.style.display = 'flex';
    setTimeout(() => m.classList.add('active'), 10);
  }
}

function closeAboutModal() {
  const m = document.getElementById('modalAboutApp');
  if (m) {
    m.classList.remove('active');
    setTimeout(() => m.style.display = 'none', 200);
  }
}

function updateThemeVisuals() {
  const currentTheme = localStorage.getItem('maoneart_theme') || 'dark';
  const darkCard = document.getElementById('themeCardDark');
  const lightCard = document.getElementById('themeCardLight');
  if (darkCard && lightCard) {
    darkCard.classList.toggle('active', currentTheme === 'dark');
    lightCard.classList.toggle('active', currentTheme === 'light');
    const darkCheck = darkCard.querySelector('.check-icon');
    const lightCheck = lightCard.querySelector('.check-icon');
    if (darkCheck) darkCheck.style.display = (currentTheme === 'dark') ? 'inline-block' : 'none';
    if (lightCheck) lightCheck.style.display = (currentTheme === 'light') ? 'inline-block' : 'none';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  loadSavedToken();
  updateThemeVisuals();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
