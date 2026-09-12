<?php
// pengaturan.php - Pengaturan Sistem Gaya iPhone (iOS Grouped List & Sub-Pages)
$pageTitle = "Pengaturan Sistem";
require_once __DIR__ . '/config/database.php';

// Sub-page route handler
$sub = trim($_GET['sub'] ?? '');

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

        header('Location: pengaturan.php?sub=profil');
        exit;
    }

    // Simpan Konfigurasi Koneksi Database & Server URL
    if ($_POST['action'] === 'simpan_koneksi') {
        $newHost      = trim($_POST['db_host'] ?? '127.0.0.1');
        $newPort      = trim($_POST['db_port'] ?? '3306');
        $newDbname    = trim($_POST['db_name'] ?? 'db_inventory');
        $newUser      = trim($_POST['db_user'] ?? 'root');
        $newPass      = (string)($_POST['db_pass'] ?? '');
        $newServerUrl = trim($_POST['server_url'] ?? '');

        // Uji koneksi terlebih dahulu
        $test = testDbConnection($newHost, $newPort, $newDbname, $newUser, $newPass);
        if ($test['success']) {
            saveDbConfig($newHost, $newPort, $newDbname, $newUser, $newPass, $newServerUrl);
            setFlash('success', 'Koneksi Berhasil Disimpan', "Berhasil terhubung ke database '$newDbname' di host '$newHost:$newPort'.");
        } else {
            if (!empty($_POST['force_save'])) {
                saveDbConfig($newHost, $newPort, $newDbname, $newUser, $newPass, $newServerUrl);
                setFlash('info', 'Konfigurasi Disimpan (Offline)', "Konfigurasi berhasil disimpan, namun saat ini database di $newHost:$newPort belum merespon (" . $test['error'] . ").");
            } else {
                setFlash('danger', 'Gagal Menghubungkan ke Database', "Koneksi ke $newHost:$newPort gagal: " . $test['error'] . ". Pastikan server database aktif dan port terbuka.");
            }
        }

        header('Location: pengaturan.php?sub=jaringan');
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

        header('Location: pengaturan.php?sub=maintenance');
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

<style>
/* iOS / iPhone Settings Styles for Web */
.ios-section-header {
  font-size: 0.73rem;
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin: 20px 0 6px 14px;
}
.ios-group-container {
  background: var(--card-bg);
  border: 1px solid var(--card-border);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
  margin-bottom: 12px;
}
.ios-list-item {
  display: flex;
  align-items: center;
  padding: 13px 16px;
  color: var(--text-main);
  text-decoration: none;
  transition: background 0.15s ease, transform 0.1s ease;
  border-bottom: 1px solid var(--card-border);
}
.ios-list-item:last-child {
  border-bottom: none;
}
.ios-list-item:hover {
  background: rgba(255, 255, 255, 0.04);
  color: var(--text-main);
}
.ios-list-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ffffff;
  font-size: 1.05rem;
  margin-right: 14px;
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}
.ios-list-content {
  flex: 1;
  min-width: 0;
}
.ios-list-title {
  font-size: 0.92rem;
  font-weight: 600;
  color: var(--text-main);
  line-height: 1.25;
}
.ios-list-subtitle {
  font-size: 0.74rem;
  color: var(--text-muted);
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.ios-list-trailing {
  display: flex;
  align-items: center;
  gap: 6px;
  color: var(--text-muted);
  font-size: 0.8rem;
  margin-left: 10px;
}
.ios-sub-back {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: #2563eb;
  font-weight: 700;
  font-size: 0.88rem;
  text-decoration: none;
  padding: 6px 12px;
  border-radius: 99px;
  background: rgba(37, 99, 235, 0.1);
  transition: all 0.15s ease;
}
.ios-sub-back:hover {
  background: rgba(37, 99, 235, 0.2);
  color: #1d4ed8;
}
</style>

<?php if (empty($sub)): ?>
  <!-- ========================================================================= -->
  <!-- 1. HALAMAN UTAMA PENGATURAN GAYA IPHONE (iOS GROUPED LIST VIEW)           -->
  <!-- ========================================================================= -->

  <div class="ios-top-bar">
    <a href="index.php" class="ios-circle-back" title="Kembali ke Dashboard">
      <i class="bi bi-chevron-left"></i>
    </a>
    <h1 class="ios-bar-title">Pengaturan</h1>
    <div class="ios-bar-action">
      <button type="button" class="btn btn-secondary btn-sm" onclick="showIntroWalkthrough(true)" style="border-radius: 99px; width: 34px; height: 34px; padding: 0;" title="Buka Panduan">
        <i class="bi bi-info-circle-fill" style="color: #38bdf8;"></i>
      </button>
    </div>
  </div>

  <!-- Profil Pengguna Gudang (iOS Profile Banner) -->
  <div class="ios-group-container" style="margin-top: 4px;">
    <div class="ios-list-item" style="padding: 16px;">
      <div style="width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #007AFF, #5856D6); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.4rem; font-weight: 800; margin-right: 14px; flex-shrink: 0; box-shadow: 0 4px 12px rgba(0, 122, 255, 0.35);">
        OP
      </div>
      <div class="ios-list-content">
        <div style="font-size: 1.05rem; font-weight: 700; color: var(--text-main);">Petugas Gudang Pabrik</div>
        <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
          Operator Lapangan • <?= htmlspecialchars($currNamaGudang) ?>
        </div>
      </div>
      <div class="ios-list-trailing">
        <span class="badge <?= $pdo ? 'badge-success' : 'badge-danger' ?>" style="font-size: 0.7rem; padding: 4px 8px;">
          <i class="bi bi-circle-fill" style="font-size: 0.45rem;"></i> <?= $pdo ? 'Online' : 'Offline' ?>
        </span>
      </div>
    </div>
  </div>

  <!-- Section 1: Jaringan & Server -->
  <div class="ios-section-header">JARINGAN & SERVER</div>
  <div class="ios-group-container">
    <a href="pengaturan.php?sub=jaringan" class="ios-list-item">
      <div class="ios-list-icon" style="background: #007AFF;">
        <i class="bi bi-wifi"></i>
      </div>
      <div class="ios-list-content">
        <div class="ios-list-title">Koneksi Server & Database</div>
        <div class="ios-list-subtitle">Host: <?= htmlspecialchars($host) ?>:<?= htmlspecialchars($port) ?> • <?= htmlspecialchars($dbname) ?></div>
      </div>
      <div class="ios-list-trailing">
        <span style="color: #059669; font-weight: 600;"><?= $pdo ? 'Terhubung' : 'Terputus' ?></span>
        <i class="bi bi-chevron-right"></i>
      </div>
    </a>
  </div>

  <!-- Section 2: Tampilan & AI -->
  <div class="ios-section-header">TAMPILAN & KECERDASAN BUATAN</div>
  <div class="ios-group-container">
    <a href="pengaturan.php?sub=tema" class="ios-list-item">
      <div class="ios-list-icon" style="background: #AF52DE;">
        <i class="bi bi-palette-fill"></i>
      </div>
      <div class="ios-list-content">
        <div class="ios-list-title">Tema Tampilan Sistem</div>
        <div class="ios-list-subtitle">Dark Navy Industrial & Clean White Modern</div>
      </div>
      <div class="ios-list-trailing">
        <i class="bi bi-chevron-right"></i>
      </div>
    </a>

    <a href="pengaturan.php?sub=ai" class="ios-list-item">
      <div class="ios-list-icon" style="background: #5856D6;">
        <i class="bi bi-robot"></i>
      </div>
      <div class="ios-list-content">
        <div class="ios-list-title">Kecerdasan Buatan (Si-nya AI)</div>
        <div class="ios-list-subtitle">Google Gemini API Key • Analisis Stok Realtime</div>
      </div>
      <div class="ios-list-trailing">
        <i class="bi bi-chevron-right"></i>
      </div>
    </a>
  </div>

  <!-- Section 3: Organisasi & Gudang -->
  <div class="ios-section-header">IDENTITAS & PERUSAHAAN</div>
  <div class="ios-group-container">
    <a href="pengaturan.php?sub=profil" class="ios-list-item">
      <div class="ios-list-icon" style="background: #34C759;">
        <i class="bi bi-building"></i>
      </div>
      <div class="ios-list-content">
        <div class="ios-list-title">Identitas Gudang & Kantor</div>
        <div class="ios-list-subtitle"><?= htmlspecialchars($currNamaKantor) ?> • <?= htmlspecialchars($currNamaGudang) ?></div>
      </div>
      <div class="ios-list-trailing">
        <i class="bi bi-chevron-right"></i>
      </div>
    </a>
  </div>

  <!-- Section 4: Panduan & Informasi -->
  <div class="ios-section-header">PANDUAN & BANTUAN</div>
  <div class="ios-group-container">
    <div class="ios-list-item" onclick="showIntroWalkthrough(true)" style="cursor: pointer;">
      <div class="ios-list-icon" style="background: #FF9500;">
        <i class="bi bi-play-circle-fill"></i>
      </div>
      <div class="ios-list-content">
        <div class="ios-list-title">Panduan Aplikasi (3 Slide)</div>
        <div class="ios-list-subtitle">Buka slide tutorial pengenalan fitur gudang & AI</div>
      </div>
      <div class="ios-list-trailing">
        <i class="bi bi-chevron-right"></i>
      </div>
    </div>

    <div class="ios-list-item" onclick="openAboutModal()" style="cursor: pointer;">
      <div class="ios-list-icon" style="background: #0284C7;">
        <i class="bi bi-info-circle-fill"></i>
      </div>
      <div class="ios-list-content">
        <div class="ios-list-title">Tentang MaoneArt Inventory</div>
        <div class="ios-list-subtitle">Versi 2.0.0 Enterprise • Hermawan (MaoneArt)</div>
      </div>
      <div class="ios-list-trailing">
        <i class="bi bi-chevron-right"></i>
      </div>
    </div>
  </div>

  <!-- Section 5: Pemeliharaan -->
  <div class="ios-section-header">SISTEM & PEMELIHARAAN</div>
  <div class="ios-group-container">
    <a href="pengaturan.php?sub=maintenance" class="ios-list-item">
      <div class="ios-list-icon" style="background: #FF3B30;">
        <i class="bi bi-shield-shaded"></i>
      </div>
      <div class="ios-list-content">
        <div class="ios-list-title" style="color: #ef4444;">Pemeliharaan & Zona Bahaya</div>
        <div class="ios-list-subtitle">Backup Database SQL, phpMyAdmin & Reset Data</div>
      </div>
      <div class="ios-list-trailing">
        <i class="bi bi-chevron-right"></i>
      </div>
    </a>
  </div>

  <div style="text-align: center; margin-top: 24px; font-size: 0.75rem; color: var(--text-muted); line-height: 1.5;">
    MaoneArt Inventory System • Versi 2.0.0 Enterprise<br>
    Dikembangkan oleh <strong>Hermawan (MaoneArt)</strong>
  </div>

<?php elseif ($sub === 'jaringan'): ?>
  <!-- ========================================================================= -->
  <!-- 2. SUB-HALAMAN: KONEKSI JARINGAN & SERVER (MASUK SETELAH DIKLIK)         -->
  <!-- ========================================================================= -->

  <div class="ios-top-bar">
    <a href="pengaturan.php" class="ios-sub-back">
      <i class="bi bi-chevron-left"></i> Pengaturan
    </a>
    <h1 class="ios-bar-title">Jaringan & Server</h1>
    <div class="ios-bar-action"></div>
  </div>

  <div class="ios-form-card">
    <div class="ios-group-title" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
      <span><i class="bi bi-hdd-network-fill"></i> KONEKSI SERVER & DATABASE</span>
      <span class="badge <?= $pdo ? 'badge-success' : 'badge-danger' ?>" style="font-size: 0.68rem; padding: 4px 8px;">
        <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> <?= $pdo ? 'Terhubung (' . htmlspecialchars($host) . ')' : 'Terputus' ?>
      </span>
    </div>

    <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 14px;">
      Atur alamat akses web dan host database MySQL. Anda dapat dengan mudah mengalihkan sistem dari database lokal HP (Termux) ke komputer laptop / server kantor.
    </p>

    <!-- Pilihan Mode Cepat (Presets) -->
    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px;">
      <button type="button" class="btn btn-secondary btn-sm" onclick="setPresetMode('hp')" style="border-radius: 10px; font-size: 0.78rem; font-weight: 700;">
        📱 Mode HP (127.0.0.1)
      </button>
      <button type="button" class="btn btn-secondary btn-sm" onclick="setPresetMode('laptop')" style="border-radius: 10px; font-size: 0.78rem; font-weight: 700;">
        💻 Mode Laptop / Server Kantor
      </button>
    </div>

    <form action="pengaturan.php" method="POST" id="formKoneksi">
      <input type="hidden" name="action" value="simpan_koneksi">

      <div style="margin-bottom: 14px;">
        <label class="ios-label">Alamat Akses Saat Ini (Web / APK Base URL) <span style="color: #ef4444;">*</span></label>
        <input type="text" name="server_url" id="inputServerUrl" class="ios-input" value="<?= htmlspecialchars($customServerUrl ?: "http://$serverHost/inventory") ?>" placeholder="http://192.168.1.100:8085/inventory" required style="color: #2563eb; font-weight: 700;">
        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 4px;">
          URL ini dipakai browser dan APK untuk menghubungkan transaksi ke server.
        </div>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 14px;">
        <div>
          <label class="ios-label">Host Database (IP Server / Laptop) <span style="color: #ef4444;">*</span></label>
          <input type="text" name="db_host" id="inputDbHost" class="ios-input" value="<?= htmlspecialchars($host) ?>" placeholder="127.0.0.1 atau 192.168.x.x" required style="font-weight: 700;">
        </div>

        <div>
          <label class="ios-label">Port MySQL / MariaDB</label>
          <input type="number" name="db_port" id="inputDbPort" class="ios-input" value="<?= htmlspecialchars($port) ?>" placeholder="3306" required>
        </div>

        <div>
          <label class="ios-label">Nama Database</label>
          <input type="text" name="db_name" id="inputDbName" class="ios-input" value="<?= htmlspecialchars($dbname) ?>" placeholder="db_inventory" required>
        </div>

        <div>
          <label class="ios-label">Username Database</label>
          <input type="text" name="db_user" id="inputDbUser" class="ios-input" value="<?= htmlspecialchars($username) ?>" placeholder="root" required>
        </div>

        <div style="grid-column: span 2;">
          <label class="ios-label">Password Database (Opsional)</label>
          <input type="password" name="db_pass" id="inputDbPass" class="ios-input" value="<?= htmlspecialchars($password) ?>" placeholder="Kosongkan jika tanpa password (standar XAMPP)">
        </div>
      </div>

      <div style="margin-top: 18px;">
        <button type="submit" class="ios-btn-primary ios-btn-blue" style="height: 46px;">
          <i class="bi bi-arrow-repeat"></i> Simpan & Hubungkan Database
        </button>
      </div>
    </form>

    <div class="info-guide-box" style="margin-top: 16px;">
      💡 <strong>Cara Pindah ke Database Laptop / Server:</strong><br>
      1. Pastikan Komputer/Laptop dan HP terhubung pada WiFi yang sama.<br>
      2. Klik tombol <strong>Mode Laptop / Server Kantor</strong> di atas.<br>
      3. Masukkan IP Komputer Kantor (misal <code>192.168.1.50</code>) pada kolom <strong>Host Database</strong>.<br>
      4. Klik <strong>Simpan & Hubungkan</strong>. Seluruh mutasi di HP otomatis membaca & menulis langsung ke server kantor!
    </div>
  </div>

<?php elseif ($sub === 'tema'): ?>
  <!-- ========================================================================= -->
  <!-- 3. SUB-HALAMAN: TEMA & TAMPILAN                                           -->
  <!-- ========================================================================= -->

  <div class="ios-top-bar">
    <a href="pengaturan.php" class="ios-sub-back">
      <i class="bi bi-chevron-left"></i> Pengaturan
    </a>
    <h1 class="ios-bar-title">Tema Tampilan</h1>
    <div class="ios-bar-action"></div>
  </div>

  <div class="ios-form-card">
    <div class="ios-group-title">
      <i class="bi bi-palette-fill"></i> PILIH MODE WARNA SISTEM
    </div>

    <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 14px;">
      Pilih gaya tampilan visual sistem. Seluruh warna kartu, form input, tabel, navigasi bar, dan latar belakang akan menyesuaikan secara instan tanpa perlu memuat ulang halaman.
    </p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px;">
      <!-- Dark Mode Card -->
      <div id="themeCardDark" class="theme-option-card active" onclick="setAppTheme('dark')" style="background: rgba(15, 23, 42, 0.85); cursor: pointer;">
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
      <div id="themeCardLight" class="theme-option-card" onclick="setAppTheme('light')" style="background: rgba(255, 255, 255, 0.95); cursor: pointer;">
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

<?php elseif ($sub === 'ai'): ?>
  <!-- ========================================================================= -->
  <!-- 4. SUB-HALAMAN: KECERDASAN BUATAN (GEMINI AI)                            -->
  <!-- ========================================================================= -->

  <div class="ios-top-bar">
    <a href="pengaturan.php" class="ios-sub-back">
      <i class="bi bi-chevron-left"></i> Pengaturan
    </a>
    <h1 class="ios-bar-title">Gemini AI Gudang</h1>
    <div class="ios-bar-action"></div>
  </div>

  <div class="ios-form-card">
    <div class="ios-group-title">
      <i class="bi bi-robot"></i> KECERDASAN BUATAN (GEMINI AI GUDANG)
    </div>

    <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 14px;">
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

<?php elseif ($sub === 'profil'): ?>
  <!-- ========================================================================= -->
  <!-- 5. SUB-HALAMAN: IDENTITAS GUDANG & PERUSAHAAN                            -->
  <!-- ========================================================================= -->

  <div class="ios-top-bar">
    <a href="pengaturan.php" class="ios-sub-back">
      <i class="bi bi-chevron-left"></i> Pengaturan
    </a>
    <h1 class="ios-bar-title">Profil Gudang</h1>
    <div class="ios-bar-action"></div>
  </div>

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

<?php elseif ($sub === 'maintenance'): ?>
  <!-- ========================================================================= -->
  <!-- 6. SUB-HALAMAN: PEMELIHARAAN & DATABASE                                   -->
  <!-- ========================================================================= -->

  <div class="ios-top-bar">
    <a href="pengaturan.php" class="ios-sub-back">
      <i class="bi bi-chevron-left"></i> Pengaturan
    </a>
    <h1 class="ios-bar-title">Pemeliharaan</h1>
    <div class="ios-bar-action"></div>
  </div>

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

<?php endif; ?>

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

function setPresetMode(mode) {
  const hostInp = document.getElementById('inputDbHost');
  const portInp = document.getElementById('inputDbPort');
  const nameInp = document.getElementById('inputDbName');
  const userInp = document.getElementById('inputDbUser');
  const passInp = document.getElementById('inputDbPass');
  const urlInp  = document.getElementById('inputServerUrl');

  if (mode === 'hp') {
    if (hostInp) hostInp.value = '127.0.0.1';
    if (portInp) portInp.value = '3306';
    if (nameInp) nameInp.value = 'db_inventory';
    if (userInp) userInp.value = 'root';
    if (passInp) passInp.value = '';
    if (urlInp)  urlInp.value  = 'http://localhost:8085/inventory';
    showAlertModal({
      title: 'Preset Mode HP (Local)',
      message: 'Parameter diisi untuk database lokal Termux (127.0.0.1). Klik "Simpan & Hubungkan Database" untuk menerapkan.',
      type: 'info'
    });
  } else if (mode === 'laptop') {
    const currentHost = (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') ? window.location.hostname : '192.168.1.100';
    if (hostInp) hostInp.value = currentHost;
    if (portInp) portInp.value = '3306';
    if (nameInp) nameInp.value = 'db_inventory';
    if (userInp) userInp.value = 'root';
    if (passInp) passInp.value = '';
    if (urlInp)  urlInp.value  = 'http://' + currentHost + ':8085/inventory';
    showAlertModal({
      title: 'Preset Mode Laptop / Server',
      message: 'Parameter disiapkan untuk server kantor/laptop. Silakan sesuaikan IP (' + currentHost + ') jika berbeda, lalu klik "Simpan & Hubungkan Database".',
      type: 'info'
    });
  }
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
  if (inp && icon) {
    if (inp.type === 'password') {
      inp.type = 'text';
      icon.className = 'bi bi-eye-slash';
    } else {
      inp.type = 'password';
      icon.className = 'bi bi-eye';
    }
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
