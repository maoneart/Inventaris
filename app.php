<?php
// app.php - Tampilan Dashboard APK Ala Gojek (Tanpa Slide Drawer Kiri)
$pageTitle = "MaoneArt Gudang APK";
require_once __DIR__ . '/config/database.php';

$totalKritis = $pdo->query("SELECT COUNT(*) FROM barang WHERE stok_saat_ini <= stok_minimum")->fetchColumn();
$totalItems = $pdo->query("SELECT COUNT(*) FROM barang")->fetchColumn();
$namaGudang = getSetting('nama_gudang', 'Gudang Pusat Logistik');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title>MaoneArt Gudang APK</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body {
      background-color: #0b0f19;
      padding-bottom: 30px;
    }
    .gojek-app-wrapper {
      max-width: 480px;
      margin: 0 auto;
      padding: 0 16px;
    }
    /* Top Bar Ala Gojek */
    .gojek-top-bar {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 14px 0 10px;
    }
    .gojek-search-box {
      flex: 1;
      height: 42px;
      background: #1e293b;
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 24px;
      display: flex;
      align-items: center;
      padding: 0 14px;
      gap: 8px;
      text-decoration: none;
      color: #94a3b8;
      font-size: 0.82rem;
    }
    .gojek-btn-icon {
      width: 40px;
      height: 40px;
      border-radius: 12px;
      background: #1e293b;
      border: 1px solid rgba(255,255,255,0.08);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #60a5fa;
      text-decoration: none;
      font-size: 1.1rem;
    }

    /* Iklan Slider / Banner Carousel */
    .carousel-container {
      position: relative;
      overflow: hidden;
      border-radius: 18px;
      margin: 10px 0 14px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
    }
    .carousel-track {
      display: flex;
      transition: transform 0.4s ease-in-out;
      width: 400%;
    }
    .carousel-slide {
      width: 25%;
      padding: 20px 18px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      text-decoration: none;
      color: #ffffff;
    }
    .slide-in {
      background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    }
    .slide-out {
      background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
    }
    .slide-ai {
      background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%);
    }
    .slide-rep {
      background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
    }
    .carousel-dots {
      display: flex;
      justify-content: center;
      gap: 5px;
      margin-bottom: 20px;
    }
    .carousel-dot {
      width: 6px;
      height: 6px;
      border-radius: 3px;
      background: #334155;
      transition: all 0.3s;
    }
    .carousel-dot.active {
      width: 20px;
      background: #60a5fa;
    }

    /* Grid Menu Ala Gojek (4 Kolom) */
    .gojek-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px 8px;
      margin-bottom: 24px;
    }
    .gojek-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-decoration: none;
      gap: 6px;
    }
    .gojek-icon-box {
      width: 52px;
      height: 52px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.45rem;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .gojek-item:active .gojek-icon-box {
      transform: scale(0.92);
    }
    .gojek-icon-box.green { background: rgba(16, 185, 129, 0.18); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.35); }
    .gojek-icon-box.red { background: rgba(239, 68, 68, 0.18); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.35); }
    .gojek-icon-box.blue { background: rgba(37, 99, 235, 0.18); color: #60a5fa; border: 1px solid rgba(37, 99, 235, 0.35); }
    .gojek-icon-box.amber { background: rgba(245, 158, 11, 0.18); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.35); }
    .gojek-icon-box.cyan { background: rgba(6, 182, 212, 0.18); color: #38bdf8; border: 1px solid rgba(6, 182, 212, 0.35); }
    .gojek-icon-box.purple { background: rgba(139, 92, 246, 0.18); color: #c084fc; border: 1px solid rgba(139, 92, 246, 0.35); }
    .gojek-icon-box.slate { background: rgba(100, 116, 139, 0.18); color: #cbd5e1; border: 1px solid rgba(100, 116, 139, 0.35); }
    .gojek-icon-box.indigo { background: rgba(79, 70, 229, 0.18); color: #818cf8; border: 1px solid rgba(79, 70, 229, 0.35); }

    .gojek-label {
      font-size: 0.72rem;
      font-weight: 700;
      color: #ffffff;
      text-align: center;
      line-height: 1.2;
    }
    .gojek-sub {
      font-size: 0.62rem;
      color: #94a3b8;
      text-align: center;
    }

    /* Gopay Style Status Card */
    .gopay-card {
      background: #161f30;
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 16px;
      padding: 14px 16px;
      margin-bottom: 20px;
    }
    .gopay-row {
      display: flex;
      justify-content: space-around;
      align-items: center;
      padding-top: 12px;
      border-top: 1px solid rgba(255,255,255,0.06);
    }
    .gopay-stat {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-decoration: none;
      color: #ffffff;
      gap: 2px;
    }
    .gopay-stat i {
      font-size: 1.1rem;
    }
  </style>
</head>
<body>

<div class="gojek-app-wrapper">

  <!-- 1. Top Bar Ala Gojek -->
  <div class="gojek-top-bar">
    <div style="width: 38px; height: 38px; border-radius: 12px; background: linear-gradient(135deg, #2563eb, #8b5cf6); display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
      📦
    </div>
    <a href="index.php" class="gojek-search-box">
      <i class="bi bi-search"></i>
      <span>Cari barang / P/N / rak gudang...</span>
    </a>
    <a href="index.php" class="gojek-btn-icon" title="Buka Web Dashboard">
      <i class="bi bi-laptop"></i>
    </a>
  </div>

  <!-- 2. Iklan Slider / Banner Carousel Ala Gojek -->
  <div class="carousel-container">
    <div class="carousel-track" id="carouselTrack">
      <!-- Slide 1: Barang Masuk -->
      <a href="masuk.php" class="carousel-slide slide-in">
        <div>
          <span style="font-size: 0.65rem; font-weight: 800; background: rgba(0,0,0,0.25); padding: 2px 7px; border-radius: 4px; letter-spacing: 0.05em;">STOCK IN</span>
          <h2 style="font-size: 1.05rem; font-weight: 800; margin: 6px 0 2px;">Penerimaan Barang Masuk</h2>
          <p style="font-size: 0.72rem; opacity: 0.9; margin: 0;">Input surat jalan dari supplier lebih cepat & akurat.</p>
        </div>
        <div style="font-size: 2.2rem; opacity: 0.9;"><i class="bi bi-box-arrow-in-down"></i></div>
      </a>

      <!-- Slide 2: Barang Keluar -->
      <a href="keluar.php" class="carousel-slide slide-out">
        <div>
          <span style="font-size: 0.65rem; font-weight: 800; background: rgba(0,0,0,0.25); padding: 2px 7px; border-radius: 4px; letter-spacing: 0.05em;">STOCK OUT</span>
          <h2 style="font-size: 1.05rem; font-weight: 800; margin: 6px 0 2px;">Pengeluaran Tools & Material</h2>
          <p style="font-size: 0.72rem; opacity: 0.9; margin: 0;">Catat pengambilan oleh teknisi / PIC kerja.</p>
        </div>
        <div style="font-size: 2.2rem; opacity: 0.9;"><i class="bi bi-box-arrow-up-right"></i></div>
      </a>

      <!-- Slide 3: Tanya AI -->
      <a href="tanya_ai.php" class="carousel-slide slide-ai">
        <div>
          <span style="font-size: 0.65rem; font-weight: 800; background: rgba(0,0,0,0.25); padding: 2px 7px; border-radius: 4px; letter-spacing: 0.05em;">AI ASSISTANT</span>
          <h2 style="font-size: 1.05rem; font-weight: 800; margin: 6px 0 2px;">Si-nya: Asisten AI Gudang</h2>
          <p style="font-size: 0.72rem; opacity: 0.9; margin: 0;">Tanya stok natural & buat draf laporan otomatis.</p>
        </div>
        <div style="font-size: 2.2rem; opacity: 0.9;"><i class="bi bi-robot"></i></div>
      </a>

      <!-- Slide 4: Laporan Mutasi -->
      <a href="laporan.php" class="carousel-slide slide-rep">
        <div>
          <span style="font-size: 0.65rem; font-weight: 800; background: rgba(0,0,0,0.25); padding: 2px 7px; border-radius: 4px; letter-spacing: 0.05em;">LAPORAN</span>
          <h2 style="font-size: 1.05rem; font-weight: 800; margin: 6px 0 2px;">Rekapitulasi Arus Barang</h2>
          <p style="font-size: 0.72rem; opacity: 0.9; margin: 0;">Cetak format PDF & unduh Excel siap audit.</p>
        </div>
        <div style="font-size: 2.2rem; opacity: 0.9;"><i class="bi bi-file-earmark-text"></i></div>
      </a>
    </div>
  </div>

  <!-- Carousel Dots Indicator -->
  <div class="carousel-dots" id="carouselDots">
    <div class="carousel-dot active"></div>
    <div class="carousel-dot"></div>
    <div class="carousel-dot"></div>
    <div class="carousel-dot"></div>
  </div>

  <!-- 3. Grid Tombol Menu Ala Gojek (4 Kolom Ikon Bersih) -->
  <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; letter-spacing: 0.06em; margin-bottom: 12px;">
    MENU LAYANAN GUDANG
  </div>

  <div class="gojek-grid">
    <!-- 1. Barang Masuk -->
    <a href="masuk.php" class="gojek-item">
      <div class="gojek-icon-box green">
        <i class="bi bi-box-arrow-in-down"></i>
      </div>
      <div class="gojek-label">Brg Masuk</div>
      <div class="gojek-sub">Supplier</div>
    </a>

    <!-- 2. Barang Keluar -->
    <a href="keluar.php" class="gojek-item">
      <div class="gojek-icon-box red">
        <i class="bi bi-box-arrow-up-right"></i>
      </div>
      <div class="gojek-label">Brg Keluar</div>
      <div class="gojek-sub">Ke PIC</div>
    </a>

    <!-- 3. Data Barang & P/N -->
    <a href="barang.php" class="gojek-item">
      <div class="gojek-icon-box blue">
        <i class="bi bi-boxes"></i>
      </div>
      <div class="gojek-label">Data Barang</div>
      <div class="gojek-sub">Katalog & P/N</div>
    </a>

    <!-- 4. Data Supplier -->
    <a href="supplier.php" class="gojek-item">
      <div class="gojek-icon-box amber">
        <i class="bi bi-truck"></i>
      </div>
      <div class="gojek-label">Supplier</div>
      <div class="gojek-sub">Rekanan</div>
    </a>

    <!-- 5. Data PIC -->
    <a href="pic.php" class="gojek-item">
      <div class="gojek-icon-box cyan">
        <i class="bi bi-people-fill"></i>
      </div>
      <div class="gojek-label">Data PIC</div>
      <div class="gojek-sub">Peminta Tools</div>
    </a>

    <!-- 6. Tanya AI -->
    <a href="tanya_ai.php" class="gojek-item">
      <div class="gojek-icon-box purple">
        <i class="bi bi-robot"></i>
      </div>
      <div class="gojek-label">Tanya AI</div>
      <div class="gojek-sub">Si-nya Asisten</div>
    </a>

    <!-- 7. Laporan Mutasi -->
    <a href="laporan.php" class="gojek-item">
      <div class="gojek-icon-box slate">
        <i class="bi bi-file-earmark-bar-graph"></i>
      </div>
      <div class="gojek-label">Laporan</div>
      <div class="gojek-sub">Mutasi Arus</div>
    </a>

    <!-- 8. Web Portal -->
    <a href="index.php" class="gojek-item">
      <div class="gojek-icon-box indigo">
        <i class="bi bi-grid-1x2-fill"></i>
      </div>
      <div class="gojek-label">Web Portal</div>
      <div class="gojek-sub">Dashboard</div>
    </a>
  </div>

  <!-- 4. Gopay Style Status Card -->
  <div class="gopay-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
      <div style="display: flex; align-items: center; gap: 6px;">
        <i class="bi bi-geo-alt-fill text-primary"></i>
        <span style="font-size: 0.82rem; font-weight: 700; color: #ffffff;"><?= htmlspecialchars($namaGudang) ?></span>
      </div>
      <span class="badge badge-success" style="font-size: 0.65rem;"><i class="bi bi-wifi"></i> SERVER OK</span>
    </div>

    <div class="gopay-row">
      <a href="index.php" class="gopay-stat">
        <i class="bi bi-boxes text-info"></i>
        <span style="font-size: 0.85rem; font-weight: 800;"><?= number_format($totalItems) ?></span>
        <span style="font-size: 0.65rem; color: #94a3b8;">Total Barang</span>
      </a>

      <div style="width: 1px; height: 30px; background: rgba(255,255,255,0.08);"></div>

      <a href="tanya_ai.php" class="gopay-stat">
        <i class="bi bi-exclamation-triangle-fill <?= $totalKritis > 0 ? 'text-danger' : 'text-success' ?>"></i>
        <span style="font-size: 0.85rem; font-weight: 800; <?= $totalKritis > 0 ? 'color: #f87171;' : '' ?>"><?= number_format($totalKritis) ?></span>
        <span style="font-size: 0.65rem; color: #94a3b8;">Stok Menipis</span>
      </a>

      <div style="width: 1px; height: 30px; background: rgba(255,255,255,0.08);"></div>

      <a href="export.php?type=stok_pdf" target="_blank" class="gopay-stat">
        <i class="bi bi-printer-fill text-warning"></i>
        <span style="font-size: 0.85rem; font-weight: 800;">PDF/XLS</span>
        <span style="font-size: 0.65rem; color: #94a3b8;">Unduh Cepat</span>
      </a>
    </div>
  </div>

  <!-- 5. Banner Cepat Tanya AI Bawah -->
  <a href="tanya_ai.php" style="display: flex; align-items: center; gap: 12px; padding: 14px; background: linear-gradient(135deg, rgba(139,92,246,0.15) 0%, rgba(30,41,59,0.8) 100%); border: 1px solid rgba(139,92,246,0.3); border-radius: 14px; text-decoration: none; color: #ffffff;">
    <div style="width: 42px; height: 42px; border-radius: 12px; background: rgba(139,92,246,0.25); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: #c084fc;">
      🤖
    </div>
    <div style="flex: 1;">
      <div style="font-weight: 700; font-size: 0.85rem;">Butuh Rekap Cepat?</div>
      <div style="font-size: 0.72rem; color: #94a3b8;">Tanya Si-nya AI untuk ringkasan barang masuk & keluar</div>
    </div>
    <i class="bi bi-chevron-right text-purple"></i>
  </a>

</div>

<script>
// Auto Slider Carousel Ala Iklan Gojek
document.addEventListener('DOMContentLoaded', () => {
  const track = document.getElementById('carouselTrack');
  const dots = document.querySelectorAll('.carousel-dot');
  let currentSlide = 0;
  const totalSlides = 4;

  function goToSlide(index) {
    currentSlide = index;
    track.style.transform = `translateX(-${currentSlide * 25}%)`;
    dots.forEach((dot, i) => {
      dot.classList.toggle('active', i === currentSlide);
    });
  }

  setInterval(() => {
    let next = (currentSlide + 1) % totalSlides;
    goToSlide(next);
  }, 4000);
});
</script>

</body>
</html>
