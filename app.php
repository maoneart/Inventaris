<?php
// app.php - Tampilan Khusus Mobile / APK Petugas Lapangan & Gudang
$pageTitle = "Aplikasi Mobile Gudang";
require_once __DIR__ . '/config/database.php';

// Ambil Status Cepat
$totalKritis = $pdo->query("SELECT COUNT(*) FROM barang WHERE stok_saat_ini <= stok_minimum")->fetchColumn();
$totalItems = $pdo->query("SELECT COUNT(*) FROM barang")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title>Gudang APK - Input Barang</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body {
      background: #0f172a;
      padding-bottom: 30px;
    }
    .apk-card-btn {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 22px;
      border-radius: 20px;
      text-decoration: none;
      transition: transform 0.2s, box-shadow 0.2s;
      margin-bottom: 16px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    }
    .apk-card-btn:active {
      transform: scale(0.97);
    }
    .apk-card-btn.masuk {
      background: linear-gradient(135deg, #059669 0%, #10b981 100%);
      color: #ffffff;
    }
    .apk-card-btn.keluar {
      background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
      color: #ffffff;
    }
    .apk-card-btn.ai {
      background: linear-gradient(135deg, #6d28d9 0%, #8b5cf6 100%);
      color: #ffffff;
    }
    .apk-card-btn.stok {
      background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
      color: #ffffff;
    }
    .apk-icon {
      width: 58px;
      height: 58px;
      border-radius: 16px;
      background: rgba(255, 255, 255, 0.2);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.9rem;
      flex-shrink: 0;
    }
  </style>
</head>
<body>

<div style="max-width: 480px; margin: 0 auto; padding: 20px 16px;">
  <!-- Header Mode APK -->
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div style="display: flex; align-items: center; gap: 12px;">
      <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #2563eb, #8b5cf6); display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
        📦
      </div>
      <div>
        <h1 style="font-size: 1.15rem; font-weight: 800; color: #ffffff; margin: 0;">MaoneArt Gudang</h1>
        <p style="font-size: 0.72rem; color: #94a3b8; margin: 0;">APK Input Masuk & Keluar Barang</p>
      </div>
    </div>
    <div id="connectionStatus" style="display: flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4);">
      <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981; display: inline-block;"></span>
      WiFi Server OK
    </div>
  </div>

  <!-- Big Thumb Touch Action Buttons -->
  <a href="masuk.php" class="apk-card-btn masuk">
    <div class="apk-icon">
      <i class="bi bi-box-arrow-in-down"></i>
    </div>
    <div>
      <h2 style="font-size: 1.25rem; font-weight: 800;">INPUT BARANG MASUK</h2>
      <p style="font-size: 0.78rem; opacity: 0.9; margin-top: 4px;">Penerimaan dari Supplier & No. Surat Jalan</p>
    </div>
  </a>

  <a href="keluar.php" class="apk-card-btn keluar">
    <div class="apk-icon">
      <i class="bi bi-box-arrow-up-right"></i>
    </div>
    <div>
      <h2 style="font-size: 1.25rem; font-weight: 800;">INPUT BARANG KELUAR</h2>
      <p style="font-size: 0.78rem; opacity: 0.9; margin-top: 4px;">Pengambilan Tools / Material oleh PIC</p>
    </div>
  </a>

  <a href="tanya_ai.php" class="apk-card-btn ai">
    <div class="apk-icon">
      <i class="bi bi-robot"></i>
    </div>
    <div>
      <h2 style="font-size: 1.25rem; font-weight: 800;">TANYA SI-NYA (AI)</h2>
      <p style="font-size: 0.78rem; opacity: 0.9; margin-top: 4px;">Tanya sisa stok & minta rekap laporan instan</p>
    </div>
  </a>

  <a href="index.php" class="apk-card-btn stok">
    <div class="apk-icon">
      <i class="bi bi-grid-1x2-fill"></i>
    </div>
    <div>
      <h2 style="font-size: 1.25rem; font-weight: 800;">LIHAT STOK REALTIME</h2>
      <p style="font-size: 0.78rem; opacity: 0.9; margin-top: 4px;"><?= $totalItems ?> Jenis Barang (<?= $totalKritis ?> Kritis/Menipis)</p>
    </div>
  </a>

  <!-- Tombol Pindah ke Web Dashboard -->
  <div style="text-align: center; margin-top: 24px;">
    <a href="index.php" style="font-size: 0.8rem; color: #94a3b8; text-decoration: none;">
      <i class="bi bi-laptop"></i> Buka Web Base Dashboard Lengkap
    </a>
  </div>
</div>

<script>
// Check Koneksi Jaringan WiFi / Server
function checkNet() {
  const badge = document.getElementById('connectionStatus');
  if (navigator.onLine) {
    badge.style.background = 'rgba(16, 185, 129, 0.2)';
    badge.style.color = '#34d399';
    badge.style.borderColor = 'rgba(16, 185, 129, 0.4)';
    badge.innerHTML = '<span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981; display: inline-block;"></span> WiFi Server OK';
  } else {
    badge.style.background = 'rgba(239, 68, 68, 0.2)';
    badge.style.color = '#f87171';
    badge.style.borderColor = 'rgba(239, 68, 68, 0.4)';
    badge.innerHTML = '<span style="width: 8px; height: 8px; border-radius: 50%; background: #ef4444; display: inline-block;"></span> Offline Mode';
  }
}
window.addEventListener('online', checkNet);
window.addEventListener('offline', checkNet);
</script>

</body>
</html>
