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

<!-- iPhone Style Navigation Header -->
<div class="ios-nav-header">
  <a href="index.php" class="ios-back-btn">
    <i class="bi bi-chevron-left"></i> Kembali
  </a>
  <div class="ios-header-center">
    <h1 class="ios-header-title">Pengaturan</h1>
    <p class="ios-header-subtitle">Server Kantor, AI Gemini & Profil Gudang</p>
  </div>
  <span class="badge badge-info" style="font-size: 0.7rem;">SETTINGS</span>
</div>

<!-- Group 1: Koneksi Server & Jaringan Kantor -->
<div class="ios-form-card">
  <div class="ios-group-title">
    <i class="bi bi-hdd-network-fill"></i> KONEKSI SERVER & JARINGAN KANTOR
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
    <div>
      <label class="ios-label">Status Server Local</label>
      <div style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px; padding: 11px 14px; display: flex; align-items: center; gap: 8px; font-weight: 700; color: #34d399; font-size: 0.88rem;">
        <span style="width: 10px; height: 10px; border-radius: 50%; background: #10b981; display: inline-block; box-shadow: 0 0 10px #10b981;"></span>
        Online Apache Port 8085
      </div>
    </div>

    <div>
      <label class="ios-label">Alamat Akses Saat Ini</label>
      <input type="text" class="ios-input" value="http://<?= htmlspecialchars($serverHost) ?>/Inventaris" readonly style="background: rgba(0,0,0,0.25); color: #60a5fa; font-weight: 700;">
    </div>

    <div>
      <label class="ios-label">Database MySQL / MariaDB</label>
      <div style="background: rgba(37, 99, 235, 0.12); border: 1px solid rgba(37, 99, 235, 0.3); border-radius: 12px; padding: 11px 14px; font-size: 0.88rem; color: #93c5fd;">
        <i class="bi bi-database-check text-primary"></i> db_inventaris (Port 3306)
      </div>
    </div>
  </div>

  <div style="margin-top: 14px; background: rgba(30, 41, 59, 0.5); border: 1px dashed rgba(255,255,255,0.15); border-radius: 14px; padding: 14px; font-size: 0.82rem; color: #cbd5e1; line-height: 1.6;">
    💡 <strong>Cara Pakai di Komputer & WiFi Kantor:</strong><br>
    1. Pastikan Komputer Kantor dan HP terhubung pada WiFi yang sama.<br>
    2. Cek IP Komputer Kantor (misal: <code>192.168.1.100</code>).<br>
    3. Di HP atau aplikasi APK, ganti Server URL menjadi: <code>http://192.168.1.100:8085/Inventaris</code>.<br>
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
      <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(37, 99, 235, 0.2); color: #60a5fa; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
        <i class="bi bi-download"></i>
      </div>
      <div>
        <div style="font-weight: 700; color: #ffffff; font-size: 0.88rem;">Download Backup SQL</div>
        <div style="font-size: 0.72rem; color: #94a3b8;">Cadangan database db_inventaris</div>
      </div>
    </a>

    <a href="http://localhost:8085/phpmyadmin" target="_blank" class="btn btn-secondary" style="border-radius: 14px; padding: 14px; text-align: left; display: flex; align-items: center; gap: 12px;">
      <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(245, 158, 11, 0.2); color: #fbbf24; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
        <i class="bi bi-database-gear"></i>
      </div>
      <div>
        <div style="font-weight: 700; color: #ffffff; font-size: 0.88rem;">Buka phpMyAdmin</div>
        <div style="font-size: 0.72rem; color: #94a3b8;">Port 8085 / phpmyadmin</div>
      </div>
    </a>
  </div>

  <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.08);">
    <div style="font-size: 0.82rem; font-weight: 700; color: #f87171; margin-bottom: 6px;">Zona Berbahaya (Maintenance)</div>
    <p style="font-size: 0.75rem; color: #94a3b8; margin-bottom: 12px;">Hapus seluruh transaksi masuk & keluar jika Anda ingin memulai pencatatan stok dari nol.</p>
    <button type="button" class="btn btn-danger btn-sm" onclick="confirmResetTrans()" style="border-radius: 12px; padding: 10px 18px; font-weight: 700;">
      <i class="bi bi-exclamation-triangle-fill"></i> Bersihkan Riwayat Transaksi Percobaan
    </button>
  </div>
</div>

<!-- Group 5: Tentang Aplikasi & Lisensi -->
<div class="ios-form-card" style="text-align: center; padding: 24px 16px;">
  <div style="width: 52px; height: 52px; margin: 0 auto 12px; border-radius: 16px; background: linear-gradient(135deg, #2563eb, #1d4ed8); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: #fff; box-shadow: 0 8px 24px rgba(37, 99, 235, 0.4);">
    📦
  </div>
  <h3 style="font-size: 1.05rem; font-weight: 800; color: #ffffff; margin-bottom: 4px;">MaoneArt Stock & Inventory System</h3>
  <p style="font-size: 0.78rem; color: #60a5fa; font-weight: 700; margin-bottom: 8px;">Versi 2.0.0 Enterprise Build 2026</p>
  <p style="font-size: 0.74rem; color: #94a3b8; max-width: 460px; margin: 0 auto 14px; line-height: 1.5;">
    Dikembangkan secara profesional untuk pencatatan stok gudang modern, integrasi Part Number supplier, penerimaan surat jalan, dan asisten AI.
  </p>
  <div style="font-size: 0.8rem; font-weight: 800; color: #cbd5e1;">
    Official Portal: <a href="https://maoneart.my.id" target="_blank" style="color: #38bdf8; text-decoration: none;">Maoneart.my.id</a>
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

document.addEventListener('DOMContentLoaded', loadSavedToken);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
