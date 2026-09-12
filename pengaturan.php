<?php
// pengaturan.php - Pengaturan Sistem Gaya iPhone (iOS Grouped List & Clean Sub-Pages)
$pageTitle = "Pengaturan Sistem";
require_once __DIR__ . '/config/database.php';

// Sub-page router
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
/* ==========================================================================
   TAMPILAN PENGATURAN GAYA IPHONE (iOS Clean Grouped List & Sub-Pages)
   ========================================================================== */
.ios-settings-wrap {
  max-width: 680px;
  margin: 0 auto;
  padding-bottom: 50px;
}


.ios-back-nav {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  color: #007AFF;
  font-size: 0.88rem;
  font-weight: 600;
  text-decoration: none;
  padding: 6px 12px 6px 6px;
  border-radius: 99px;
  background: rgba(0, 122, 255, 0.1);
  transition: all 0.15s ease;
  flex-shrink: 0;
}
.ios-back-nav:hover {
  background: rgba(0, 122, 255, 0.2);
  color: #0056b3;
}
.ios-back-nav i {
  font-size: 1.15rem;
  line-height: 1;
  margin-top: -1px;
}
.ios-back-nav span {
  font-size: 0.82rem;
  font-weight: 600;
  letter-spacing: -0.2px;
}

/* Header Sub-Page Khas iOS */
.ios-subpage-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 18px;
  padding: 4px 0;
}
.ios-btn-back {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #007AFF;
  font-size: 0.92rem;
  font-weight: 600;
  text-decoration: none;
  padding: 6px 12px 6px 8px;
  border-radius: 99px;
  background: rgba(0, 122, 255, 0.1);
  transition: all 0.15s ease;
}
.ios-btn-back:hover {
  background: rgba(0, 122, 255, 0.2);
  color: #0062cc;
}
.ios-subpage-title {
  font-size: 1.15rem;
  font-weight: 800;
  color: var(--text-main);
  margin: 0;
  letter-spacing: -0.3px;
}

/* Section Header Gaya iOS */
.ios-section-label {
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.6px;
  margin: 22px 0 7px 14px;
}

/* Group Container (Kotak Membulat Khas iOS) */
.ios-group-card {
  background: var(--card-bg);
  border: 1px solid var(--card-border);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  margin-bottom: 12px;
}

/* Baris Menu Gaya iOS */
.ios-item-row {
  display: flex;
  align-items: center;
  padding: 13px 16px;
  color: var(--text-main);
  text-decoration: none;
  transition: background 0.15s ease;
  border-bottom: 1px solid var(--card-border);
}
.ios-item-row:last-child {
  border-bottom: none;
}
.ios-item-row:hover {
  background: rgba(255, 255, 255, 0.04);
  color: var(--text-main);
}

/* Kotak Ikon Berwarna Khas Apple */
.ios-item-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ffffff;
  font-size: 1.1rem;
  margin-right: 14px;
  flex-shrink: 0;
}
.ios-item-content {
  flex: 1;
  min-width: 0;
}
.ios-item-title {
  font-size: 0.94rem;
  font-weight: 600;
  color: var(--text-main);
  line-height: 1.3;
}
.ios-item-subtitle {
  font-size: 0.75rem;
  color: var(--text-muted);
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.ios-item-trailing {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--text-muted);
  font-size: 0.82rem;
  margin-left: 10px;
  flex-shrink: 0;
}

/* Tabel Info Mengenai Sistem (iOS About Table) */
.ios-info-table {
  width: 100%;
  border-collapse: collapse;
}
.ios-info-table tr {
  border-bottom: 1px solid var(--card-border);
}
.ios-info-table tr:last-child {
  border-bottom: none;
}
.ios-info-table td {
  padding: 13px 16px;
  font-size: 0.88rem;
}
.ios-info-table td.label-col {
  color: var(--text-muted);
  font-weight: 500;
  width: 45%;
}
.ios-info-table td.val-col {
  color: var(--text-main);
  font-weight: 600;
  text-align: right;
}

/* Panduan Step Card */
.ios-guide-card {
  background: var(--input-bg);
  border: 1px solid var(--card-border);
  border-radius: 14px;
  padding: 16px;
  margin-bottom: 14px;
  display: flex;
  gap: 14px;
  align-items: flex-start;
}
.ios-guide-badge {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 1rem;
  flex-shrink: 0;
}
</style>

<div class="ios-settings-wrap">

<?php if (empty($sub)): ?>
  <!-- ========================================================================= -->
  <!-- 1. HALAMAN UTAMA PENGATURAN (LIST INDUK ALA IPHONE)                       -->
  <!-- ========================================================================= -->

  <div class="ios-top-bar">
    <a href="index.php" class="ios-circle-back" title="Kembali ke Dashboard">
      <i class="bi bi-chevron-left"></i>
    </a>
    <h1 class="ios-bar-title">Pengaturan</h1>
    <div class="ios-bar-action"></div>
  </div>

  <!-- Profil Pengguna Gudang (iOS Profile Banner) -->
  <div class="ios-group-card" style="margin-top: 4px;">
    <div class="ios-item-row" style="padding: 16px;">
      <div style="width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #007AFF, #5856D6); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.4rem; font-weight: 800; margin-right: 14px; flex-shrink: 0; box-shadow: 0 4px 12px rgba(0, 122, 255, 0.35);">
        OP
      </div>
      <div class="ios-item-content">
        <div style="font-size: 1.05rem; font-weight: 700; color: var(--text-main);">Petugas Gudang Pabrik</div>
        
      </div>
      <div class="ios-item-trailing">
        <span class="badge <?= $pdo ? 'badge-success' : 'badge-danger' ?>" style="font-size: 0.7rem; padding: 4px 8px;">
          <i class="bi bi-circle-fill" style="font-size: 0.45rem;"></i> <?= $pdo ? 'Online' : 'Offline' ?>
        </span>
      </div>
    </div>
  </div>

  <!-- Section 1: Jaringan & Server -->
  <div class="ios-section-label">JARINGAN & SERVER</div>
  <div class="ios-group-card">
    <a href="pengaturan.php?sub=jaringan" class="ios-item-row">
      <div class="ios-item-icon" style="background: #007AFF;">
        <i class="bi bi-wifi"></i>
      </div>
      <div class="ios-item-content">
        <div class="ios-item-title">Koneksi Server & Database</div>
        
      </div>
      <div class="ios-item-trailing">
        
        <i class="bi bi-chevron-right"></i>
      </div>
    </a>
  </div>

  <!-- Section 2: Tampilan & AI -->
  <div class="ios-section-label">TAMPILAN & KECERDASAN BUATAN</div>
  <div class="ios-group-card">
    <div class="ios-item-row" onclick="toggleWhiteMode()" style="cursor: pointer;">
      <div class="ios-item-icon" id="themeIconBox" style="background: #AF52DE;">
        <i class="bi bi-sun-fill" id="themeIcon"></i>
      </div>
      <div class="ios-item-content">
        <div class="ios-item-title">Mode White</div>
      </div>
      <div class="ios-item-trailing">
        <span id="themeStatusText" style="font-size: 0.88rem; font-weight: 700; color: #007AFF;">On</span>
        <i class="bi bi-chevron-right"></i>
      </div>
    </div>

    <a href="pengaturan.php?sub=ai" class="ios-item-row">
      <div class="ios-item-icon" style="background: #5856D6;">
        <i class="bi bi-robot"></i>
      </div>
      <div class="ios-item-content">
        <div class="ios-item-title">Kecerdasan Buatan (Si-nya AI)</div>
        
      </div>
      <div class="ios-item-trailing">
        <i class="bi bi-chevron-right"></i>
      </div>
    </a>
  </div>

  <!-- Section 3: Organisasi & Gudang -->
  <div class="ios-section-label">IDENTITAS & PERUSAHAAN</div>
  <div class="ios-group-card">
    <a href="pengaturan.php?sub=profil" class="ios-item-row">
      <div class="ios-item-icon" style="background: #34C759;">
        <i class="bi bi-building"></i>
      </div>
      <div class="ios-item-content">
        <div class="ios-item-title">Identitas Gudang & Kantor</div>
        
      </div>
      <div class="ios-item-trailing">
        <i class="bi bi-chevron-right"></i>
      </div>
    </a>
  </div>

  <!-- Section 4: Panduan & Informasi -->
  <div class="ios-section-label">PANDUAN & BANTUAN</div>
  <div class="ios-group-card">
    <a href="pengaturan.php?sub=panduan" class="ios-item-row">
      <div class="ios-item-icon" style="background: #FF9500;">
        <i class="bi bi-play-circle-fill"></i>
      </div>
      <div class="ios-item-content">
        <div class="ios-item-title">Panduan Aplikasi (3 Langkah)</div>
        
      </div>
      <div class="ios-item-trailing">
        <i class="bi bi-chevron-right"></i>
      </div>
    </a>

    <a href="pengaturan.php?sub=about" class="ios-item-row">
      <div class="ios-item-icon" style="background: #0284C7;">
        <i class="bi bi-info-circle-fill"></i>
      </div>
      <div class="ios-item-content">
        <div class="ios-item-title">Tentang MaoneArt Inventory</div>
        
      </div>
      <div class="ios-item-trailing">
        <i class="bi bi-chevron-right"></i>
      </div>
    </a>
  </div>

  <!-- Section 5: Pemeliharaan -->
  <div class="ios-section-label">SISTEM & PEMELIHARAAN</div>
  <div class="ios-group-card">
    <a href="pengaturan.php?sub=maintenance" class="ios-item-row">
      <div class="ios-item-icon" style="background: #FF3B30;">
        <i class="bi bi-shield-shaded"></i>
      </div>
      <div class="ios-item-content">
        <div class="ios-item-title" style="color: #ef4444;">Pemeliharaan & Zona Bahaya</div>
        
      </div>
      <div class="ios-item-trailing">
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
  <!-- 2. SUB-HALAMAN: KONEKSI JARINGAN & SERVER                                 -->
  <!-- ========================================================================= -->

    <div class="ios-top-bar">
    <a href="pengaturan.php" class="ios-back-nav" title="Kembali ke Pengaturan">
      <i class="bi bi-chevron-left"></i>
      <span>Pengaturan</span>
    </a>
    <h1 class="ios-bar-title">Jaringan & Server</h1>
    <div class="ios-bar-action" style="min-width: 90px;"></div>
  </div>

  <div class="ios-group-card" style="padding: 18px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
      <div style="font-weight: 800; font-size: 1rem; color: var(--text-main);">
        <i class="bi bi-hdd-network-fill" style="color: #007AFF;"></i> Status Koneksi
      </div>
      <span class="badge <?= $pdo ? 'badge-success' : 'badge-danger' ?>" style="font-size: 0.72rem; padding: 5px 10px;">
        <i class="bi bi-circle-fill" style="font-size: 0.45rem;"></i> <?= $pdo ? 'Terhubung (' . htmlspecialchars($host) . ')' : 'Terputus' ?>
      </span>
    </div>

    <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 16px;">
      Atur alamat akses web dan host database MySQL. Beralih dengan mudah dari database lokal HP (Termux) ke komputer laptop / server kantor.
    </p>

    <!-- Preset Cepat -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px;">
      <button type="button" class="btn btn-secondary btn-sm" onclick="setPresetMode('hp')" style="border-radius: 12px; padding: 10px; font-weight: 700;">
        📱 Mode HP (127.0.0.1)
      </button>
      <button type="button" class="btn btn-secondary btn-sm" onclick="setPresetMode('laptop')" style="border-radius: 12px; padding: 10px; font-weight: 700;">
        💻 Mode Laptop / Server
      </button>
    </div>

    <form action="pengaturan.php" method="POST" id="formKoneksi">
      <input type="hidden" name="action" value="simpan_koneksi">

      <div style="margin-bottom: 14px;">
        <label class="ios-label">Alamat Akses Web / Base URL <span style="color: #ef4444;">*</span></label>
        <input type="text" name="server_url" id="inputServerUrl" class="ios-input" value="<?= htmlspecialchars($customServerUrl ?: "http://$serverHost/inventory") ?>" placeholder="http://192.168.1.100:8085/inventory" required style="color: #2563eb; font-weight: 700;">
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 14px;">
        <div>
          <label class="ios-label">Host Database (IP Server) <span style="color: #ef4444;">*</span></label>
          <input type="text" name="db_host" id="inputDbHost" class="ios-input" value="<?= htmlspecialchars($host) ?>" placeholder="127.0.0.1" required style="font-weight: 700;">
        </div>

        <div>
          <label class="ios-label">Port Database</label>
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

        <div style="grid-column: 1 / -1;">
          <label class="ios-label">Password Database (Opsional)</label>
          <input type="password" name="db_pass" id="inputDbPass" class="ios-input" value="<?= htmlspecialchars($password) ?>" placeholder="Kosongkan jika tanpa password">
        </div>
      </div>

      <div style="margin-top: 16px;">
        <button type="submit" class="ios-btn-primary ios-btn-blue" style="height: 48px; border-radius: 14px;">
          <i class="bi bi-arrow-repeat"></i> Simpan & Hubungkan Database
        </button>
      </div>
    </form>
  </div>

<?php elseif ($sub === 'tema'): ?>
  <!-- ========================================================================= -->
  <!-- 3. SUB-HALAMAN: TEMA & TAMPILAN                                           -->
  <!-- ========================================================================= -->

    <div class="ios-top-bar">
    <a href="pengaturan.php" class="ios-back-nav" title="Kembali ke Pengaturan">
      <i class="bi bi-chevron-left"></i>
      <span>Pengaturan</span>
    </a>
    <h1 class="ios-bar-title">Tema Tampilan</h1>
    <div class="ios-bar-action" style="min-width: 90px;"></div>
  </div>

  <div class="ios-group-card" style="padding: 18px;">
    <div style="font-weight: 800; font-size: 1rem; color: var(--text-main); margin-bottom: 6px;">
      <i class="bi bi-palette-fill" style="color: #AF52DE;"></i> Pilih Mode Tampilan Visual
    </div>
    <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 16px;">
      Sentuh salah satu pilihan kartu di bawah untuk mengubah nuansa warna seluruh sistem secara instan.
    </p>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
      <!-- Dark Mode Card -->
      <div id="themeCardDark" class="theme-option-card active" onclick="setAppTheme('dark')" style="background: rgba(15, 23, 42, 0.9); cursor: pointer; border-radius: 14px; padding: 14px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
          <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 32px; height: 32px; border-radius: 8px; background: #1e293b; display: flex; align-items: center; justify-content: center; color: #60a5fa;">
              <i class="bi bi-moon-stars-fill"></i>
            </div>
            <div style="font-weight: 800; font-size: 0.9rem; color: #fff;">Dark Mode</div>
          </div>
          <i class="bi bi-check-circle-fill check-icon" style="font-size: 1.2rem; color: #007AFF;"></i>
        </div>
        <div style="font-size: 0.72rem; color: #94a3b8;">Dark Navy Industrial</div>
      </div>

      <!-- Light Mode Card -->
      <div id="themeCardLight" class="theme-option-card" onclick="setAppTheme('light')" style="background: #ffffff; cursor: pointer; border-radius: 14px; padding: 14px; border: 1px solid rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
          <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 32px; height: 32px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #f59e0b;">
              <i class="bi bi-sun-fill"></i>
            </div>
            <div style="font-weight: 800; font-size: 0.9rem; color: #0f172a;">Light Mode</div>
          </div>
          <i class="bi bi-check-circle-fill check-icon" style="font-size: 1.2rem; color: #007AFF; display: none;"></i>
        </div>
        <div style="font-size: 0.72rem; color: #64748b;">Clean White Modern</div>
      </div>
    </div>
  </div>

<?php elseif ($sub === 'ai'): ?>
  <!-- ========================================================================= -->
  <!-- 4. SUB-HALAMAN: KECERDASAN BUATAN (GEMINI AI)                            -->
  <!-- ========================================================================= -->

    <div class="ios-top-bar">
    <a href="pengaturan.php" class="ios-back-nav" title="Kembali ke Pengaturan">
      <i class="bi bi-chevron-left"></i>
      <span>Pengaturan</span>
    </a>
    <h1 class="ios-bar-title">Gemini AI</h1>
    <div class="ios-bar-action" style="min-width: 90px;"></div>
  </div>

  <div class="ios-group-card" style="padding: 18px;">
    <div style="font-weight: 800; font-size: 1rem; color: var(--text-main); margin-bottom: 6px;">
      <i class="bi bi-robot" style="color: #5856D6;"></i> Token Google Gemini AI
    </div>
    <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 16px;">
      🔒 <strong>Tersimpan Aman di HP:</strong> API Key hanya disimpan di browser/HP masing-masing pengguna. Token tidak pernah disimpan di source code repository GitHub.
    </p>

    <div style="margin-bottom: 16px;">
      <label class="ios-label">API Key Google Gemini</label>
      <div style="display: flex; gap: 8px;">
        <input type="password" id="inputTokenGemini" class="ios-input" placeholder="Masukkan token AIzaSy..." autocomplete="off">
        <button type="button" id="btnToggleToken" class="btn btn-secondary btn-sm" style="border-radius: 12px; padding: 0 16px;" onclick="toggleTokenVisibility()">
          <i class="bi bi-eye" id="iconEye"></i>
        </button>
      </div>
    </div>

    <!-- 2-Column Grid Symmetrical Actions -->
    <div class="grid grid-cols-2 gap-3" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
      <button type="button" class="btn btn-secondary btn-sm" onclick="clearGeminiToken()" style="border-radius: 14px; height: 48px; font-weight: 700; color: #ef4444;">
        <i class="bi bi-trash"></i> Hapus Token
      </button>
      <button type="button" class="ios-btn-primary ios-btn-blue" onclick="saveGeminiToken()" style="height: 48px; border-radius: 14px;">
        <i class="bi bi-check-lg"></i> Simpan Token
      </button>
    </div>
  </div>

<?php elseif ($sub === 'profil'): ?>
  <!-- ========================================================================= -->
  <!-- 5. SUB-HALAMAN: IDENTITAS GUDANG & PERUSAHAAN                            -->
  <!-- ========================================================================= -->

    <div class="ios-top-bar">
    <a href="pengaturan.php" class="ios-back-nav" title="Kembali ke Pengaturan">
      <i class="bi bi-chevron-left"></i>
      <span>Pengaturan</span>
    </a>
    <h1 class="ios-bar-title">Profil Gudang</h1>
    <div class="ios-bar-action" style="min-width: 90px;"></div>
  </div>

  <form action="pengaturan.php" method="POST" id="formProfil">
    <input type="hidden" name="action" value="simpan_profil">

    <div class="ios-group-card" style="padding: 18px;">
      <div style="font-weight: 800; font-size: 1rem; color: var(--text-main); margin-bottom: 16px;">
        <i class="bi bi-building" style="color: #34C759;"></i> Informasi Resmi Gudang & Kantor
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
        <div>
          <label class="ios-label">Nama Aplikasi</label>
          <input type="text" name="nama_aplikasi" class="ios-input" value="<?= htmlspecialchars($currNamaAplikasi) ?>" required>
        </div>

        <div>
          <label class="ios-label">Nama Gudang / Workshop</label>
          <input type="text" name="nama_gudang" class="ios-input" value="<?= htmlspecialchars($currNamaGudang) ?>" required>
        </div>

        <div style="grid-column: 1 / -1;">
          <label class="ios-label">Nama Perusahaan / Organisasi</label>
          <input type="text" name="nama_kantor" class="ios-input" value="<?= htmlspecialchars($currNamaKantor) ?>" required>
        </div>

        <div>
          <label class="ios-label">No. Telepon Kantor</label>
          <input type="text" name="telepon_kantor" class="ios-input" value="<?= htmlspecialchars($currTelepon) ?>">
        </div>

        <div style="grid-column: 1 / -1;">
          <label class="ios-label">Alamat Lengkap Kantor</label>
          <input type="text" name="alamat_kantor" class="ios-input" value="<?= htmlspecialchars($currAlamat) ?>">
        </div>
      </div>

      <div style="margin-top: 20px;">
        <button type="submit" class="ios-btn-primary ios-btn-green" style="height: 48px; border-radius: 14px;">
          <i class="bi bi-save-fill"></i> Simpan Profil Perusahaan
        </button>
      </div>
    </div>
  </form>

<?php elseif ($sub === 'panduan'): ?>
  <!-- ========================================================================= -->
  <!-- 6. SUB-HALAMAN: PANDUAN APLIKASI (BERSIH TANPA POPUP MENUTUPI)           -->
  <!-- ========================================================================= -->

    <div class="ios-top-bar">
    <a href="pengaturan.php" class="ios-back-nav" title="Kembali ke Pengaturan">
      <i class="bi bi-chevron-left"></i>
      <span>Pengaturan</span>
    </a>
    <h1 class="ios-bar-title">Panduan Aplikasi</h1>
    <div class="ios-bar-action" style="min-width: 90px;"></div>
  </div>

  <div class="ios-group-card" style="padding: 18px;">
    <!-- Step 1 -->
    <div class="ios-guide-card">
      <div class="ios-guide-badge" style="background: rgba(0, 170, 19, 0.15); color: #00AA13;">
        1
      </div>
      <div>
        <div style="font-weight: 800; font-size: 0.95rem; color: var(--text-main); margin-bottom: 4px;">
          Penerimaan Barang Masuk (Stock In)
        </div>
        <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.5; margin: 0;">
          Pilih rekanan supplier terlebih dahulu. Sistem otomatis menyaring part yang biasa dikirim supplier tersebut. Isi nomor surat jalan resmi dan jumlah qty kiriman.
        </p>
      </div>
    </div>

    <!-- Step 2 -->
    <div class="ios-guide-card">
      <div class="ios-guide-badge" style="background: rgba(238, 39, 55, 0.15); color: #EE2737;">
        2
      </div>
      <div>
        <div style="font-weight: 800; font-size: 0.95rem; color: var(--text-main); margin-bottom: 4px;">
          Pengeluaran Part / Tools (Stock Out)
        </div>
        <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.5; margin: 0;">
          Pilih teknisi/PIC pengambil. Cari barang dengan Live Search. Sistem otomatis memproteksi stok fisik gudang agar tidak minus saat pengeluaran.
        </p>
      </div>
    </div>

    <!-- Step 3 -->
    <div class="ios-guide-card" style="margin-bottom: 0;">
      <div class="ios-guide-badge" style="background: rgba(124, 58, 237, 0.15); color: #7C3AED;">
        3
      </div>
      <div>
        <div style="font-weight: 800; font-size: 0.95rem; color: var(--text-main); margin-bottom: 4px;">
          Tanya Asisten AI "Si-nya" & Laporan
        </div>
        <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.5; margin: 0;">
          Gunakan fitur Tanya AI untuk menanyakan stok kritis tanpa repot cari manual. Cetak dokumen mutasi PDF & Excel kapan saja di menu Laporan.
        </p>
      </div>
    </div>
  </div>

<?php elseif ($sub === 'about'): ?>
  <!-- ========================================================================= -->
  <!-- 7. SUB-HALAMAN: MENGENAI APLIKASI (GAYA IPHONE GENERAL -> ABOUT)           -->
  <!-- ========================================================================= -->

    <div class="ios-top-bar">
    <a href="pengaturan.php" class="ios-back-nav" title="Kembali ke Pengaturan">
      <i class="bi bi-chevron-left"></i>
      <span>Pengaturan</span>
    </a>
    <h1 class="ios-bar-title">Mengenai Sistem</h1>
    <div class="ios-bar-action" style="min-width: 90px;"></div>
  </div>

  <!-- App Hero Icon -->
  <div style="text-align: center; margin: 10px 0 20px;">
    <div style="width: 68px; height: 68px; border-radius: 18px; background: linear-gradient(135deg, #007AFF, #5856D6); display: inline-flex; align-items: center; justify-content: center; color: #fff; font-size: 2rem; box-shadow: 0 8px 24px rgba(0, 122, 255, 0.35);">
      📦
    </div>
    <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin: 10px 0 2px;">MaoneArt Inventory</h3>
    <div style="font-size: 0.8rem; color: var(--text-muted);">Sistem Manajemen Stok & Pergudangan Pabrik</div>
  </div>

  <div class="ios-group-card">
    <table class="ios-info-table">
      <tr>
        <td class="label-col">Nama Sistem</td>
        <td class="val-col">MaoneArt Stock & Inventory</td>
      </tr>
      <tr>
        <td class="label-col">Versi Aplikasi</td>
        <td class="val-col"><span class="badge badge-primary" style="font-size: 0.72rem;">2.0.0 Enterprise</span></td>
      </tr>
      <tr>
        <td class="label-col">Status Operasional</td>
        <td class="val-col"><span class="badge badge-success" style="font-size: 0.72rem;">Production Ready</span></td>
      </tr>
      <tr>
        <td class="label-col">Pengembang (Creator)</td>
        <td class="val-col">Hermawan (MaoneArt)</td>
      </tr>
      <tr>
        <td class="label-col">Domisili</td>
        <td class="val-col">Tambun Utara, Kab. Bekasi</td>
      </tr>
      <tr>
        <td class="label-col">Arsitektur</td>
        <td class="val-col">Hybrid Local-First (Web & APK)</td>
      </tr>
      <tr>
        <td class="label-col">Mesin Database</td>
        <td class="val-col">MariaDB / MySQL 10.x</td>
      </tr>
      <tr>
        <td class="label-col">Kecerdasan Buatan</td>
        <td class="val-col">Google Gemini 1.5 Flash</td>
      </tr>
      <tr>
        <td class="label-col">Web Resmi</td>
        <td class="val-col">
          <a href="https://maoneart.my.id" target="_blank" style="color: #007AFF; text-decoration: none; font-weight: 700;">
            maoneart.my.id <i class="bi bi-box-arrow-up-right" style="font-size: 0.7rem;"></i>
          </a>
        </td>
      </tr>
    </table>
  </div>

<?php elseif ($sub === 'maintenance'): ?>
  <!-- ========================================================================= -->
  <!-- 8. SUB-HALAMAN: PEMELIHARAAN & DATABASE                                   -->
  <!-- ========================================================================= -->

    <div class="ios-top-bar">
    <a href="pengaturan.php" class="ios-back-nav" title="Kembali ke Pengaturan">
      <i class="bi bi-chevron-left"></i>
      <span>Pengaturan</span>
    </a>
    <h1 class="ios-bar-title">Pemeliharaan</h1>
    <div class="ios-bar-action" style="min-width: 90px;"></div>
  </div>

  <div class="ios-group-card" style="padding: 18px;">
    <div style="font-weight: 800; font-size: 1rem; color: var(--text-main); margin-bottom: 14px;">
      <i class="bi bi-database-gear" style="color: #007AFF;"></i> Utilitas & Cadangan Data
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
      <a href="export.php?type=backup_db" class="btn btn-secondary" style="border-radius: 14px; padding: 14px; text-align: left; text-decoration: none;">
        <div style="color: #007AFF; font-size: 1.3rem; margin-bottom: 4px;"><i class="bi bi-download"></i></div>
        <div style="font-weight: 700; color: var(--text-main); font-size: 0.88rem;">Backup SQL</div>
        <div style="font-size: 0.72rem; color: var(--text-muted);">Unduh salinan database</div>
      </a>

      <a href="http://localhost:8085/phpmyadmin" target="_blank" class="btn btn-secondary" style="border-radius: 14px; padding: 14px; text-align: left; text-decoration: none;">
        <div style="color: #f59e0b; font-size: 1.3rem; margin-bottom: 4px;"><i class="bi bi-database-fill-gear"></i></div>
        <div style="font-weight: 700; color: var(--text-main); font-size: 0.88rem;">phpMyAdmin</div>
        <div style="font-size: 0.72rem; color: var(--text-muted);">Port 8085 / phpmyadmin</div>
      </a>
    </div>

    <div style="padding-top: 16px; border-top: 1px solid var(--card-border);">
      <div style="font-size: 0.85rem; font-weight: 800; color: #ef4444; margin-bottom: 6px;">Zona Berbahaya (Reset Data)</div>
      <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 14px;">Hapus seluruh transaksi barang masuk & keluar jika Anda ingin memulai siklus gudang dari nol.</p>
      <button type="button" class="btn btn-danger btn-sm" onclick="confirmResetTrans()" style="border-radius: 12px; padding: 10px 18px; font-weight: 700; width: 100%;">
        <i class="bi bi-exclamation-triangle-fill"></i> Bersihkan Riwayat Transaksi
      </button>
    </div>
  </div>

<?php endif; ?>

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
      message: 'Parameter disiapkan untuk server kantor/laptop (' + currentHost + '). Silakan klik "Simpan & Hubungkan Database".',
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


function toggleWhiteMode() {
  const currentTheme = localStorage.getItem('maoneart_theme') || 'dark';
  const newTheme = (currentTheme === 'light') ? 'dark' : 'light';
  setAppTheme(newTheme);
  updateThemeStatusDisplay();
}

function updateThemeStatusDisplay() {
  const currentTheme = localStorage.getItem('maoneart_theme') || 'dark';
  const txt = document.getElementById('themeStatusText');
  const icon = document.getElementById('themeIcon');
  const iconBox = document.getElementById('themeIconBox');
  const isLight = (currentTheme === 'light');

  if (txt) {
    txt.textContent = isLight ? 'On' : 'Off';
    txt.style.color = isLight ? '#007AFF' : '#8E8E93';
  }
  if (icon && iconBox) {
    icon.className = isLight ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    iconBox.style.background = isLight ? '#f59e0b' : '#AF52DE';
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
  updateThemeStatusDisplay();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
