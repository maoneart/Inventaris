<?php
// app.php - Tampilan Dashboard APK Pixel-Perfect Ala Gojek Superapp
$pageTitle = "MaoneArt Gudang (Gojek Style)";
require_once __DIR__ . '/config/database.php';

$totalKritis = $pdo->query("SELECT COUNT(*) FROM barang WHERE stok_saat_ini <= stok_minimum")->fetchColumn();
$totalItems = $pdo->query("SELECT COUNT(*) FROM barang")->fetchColumn();
$namaGudang = getSetting('nama_gudang', 'Gudang Pusat Logistik');

// Ambil 3 Transaksi Terakhir
$recentActivity = $pdo->query("
    (SELECT 'masuk' as tipe, tm.no_masuk as no_trx, s.nama_supplier as pihak, tm.total_qty as qty, tm.tanggal_masuk as tgl, tm.created_at
     FROM transaksi_masuk tm JOIN supplier s ON tm.id_supplier = s.id)
    UNION ALL
    (SELECT 'keluar' as tipe, tk.no_keluar as no_trx, p.nama_pic as pihak, tk.total_qty as qty, tk.tanggal_keluar as tgl, tk.created_at
     FROM transaksi_keluar tk JOIN pic p ON tk.id_pic = p.id)
    ORDER BY created_at DESC LIMIT 3
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title>MaoneArt Gudang</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#00aa13">
  <style>
    :root {
      --gojek-green: #00aa13;
      --gojek-dark: #0f172a;
      --gojek-card: #1e293b;
      --gojek-border: rgba(255, 255, 255, 0.08);
      --font: 'Plus Jakarta Sans', sans-serif;
    }
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: var(--font);
      -webkit-tap-highlight-color: transparent;
    }
    body {
      background-color: #0b0f19;
      color: #ffffff;
      padding-bottom: 75px;
    }
    .gojek-wrapper {
      max-width: 440px;
      margin: 0 auto;
      padding: 0 16px;
    }

    /* 1. Header & Search Bar Ala Gojek */
    .gojek-header {
      position: sticky;
      top: 0;
      z-index: 50;
      background: rgba(15, 23, 42, 0.95);
      backdrop-filter: blur(12px);
      padding: 12px 16px 10px;
      margin: 0 -16px 12px -16px;
      border-bottom: 1px solid var(--gojek-border);
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .gojek-search-pill {
      flex: 1;
      height: 40px;
      background: #1e293b;
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 999px;
      display: flex;
      align-items: center;
      padding: 0 14px;
      gap: 10px;
      text-decoration: none;
      color: #94a3b8;
      font-size: 0.82rem;
    }
    .gojek-avatar-btn {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: linear-gradient(135deg, #00aa13, #10b981);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ffffff;
      font-size: 1.1rem;
      text-decoration: none;
      flex-shrink: 0;
    }

    /* 2. Kartu Saldo / GoPay Wallet Style */
    .gopay-wallet-card {
      background: #162033;
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 18px;
      padding: 14px 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.35);
    }
    .gopay-left {
      display: flex;
      flex-direction: column;
      gap: 2px;
      border-right: 1px solid rgba(255, 255, 255, 0.1);
      padding-right: 14px;
      min-width: 125px;
    }
    .gopay-title {
      font-size: 0.7rem;
      font-weight: 800;
      color: #60a5fa;
      display: flex;
      align-items: center;
      gap: 4px;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .gopay-value {
      font-size: 1.1rem;
      font-weight: 800;
      color: #ffffff;
    }
    .gopay-sub {
      font-size: 0.65rem;
      color: #34d399;
      font-weight: 600;
    }
    .gopay-actions {
      display: flex;
      gap: 12px;
      flex: 1;
      justify-content: space-around;
      padding-left: 8px;
    }
    .gopay-action-btn {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-decoration: none;
      color: #ffffff;
      gap: 4px;
    }
    .gopay-btn-icon {
      width: 32px;
      height: 32px;
      border-radius: 10px;
      background: rgba(37, 99, 235, 0.2);
      border: 1px solid rgba(37, 99, 235, 0.4);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.95rem;
      color: #60a5fa;
    }
    .gopay-action-btn span {
      font-size: 0.65rem;
      font-weight: 700;
      color: #cbd5e1;
    }

    /* 3. Grid 8 Tombol Ikon Layanan Ala Gojek */
    .gojek-services-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 18px 10px;
      margin-bottom: 24px;
    }
    .gojek-service-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-decoration: none;
      gap: 6px;
    }
    .gojek-circle-icon {
      width: 52px;
      height: 52px;
      border-radius: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      transition: transform 0.15s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    }
    .gojek-service-item:active .gojek-circle-icon {
      transform: scale(0.9);
    }
    /* Warna Ikon Khas Layanan Gojek */
    .icon-gomasuk { background: #00aa13; color: #ffffff; }     /* Gojek Green */
    .icon-gokeluar { background: #ee2737; color: #ffffff; }    /* Gojek Red */
    .icon-gobarang { background: #0081a0; color: #ffffff; }    /* Gojek Blue */
    .icon-gosupplier { background: #df6b00; color: #ffffff; }  /* Gojek Orange */
    .icon-gopic { background: #00a3a6; color: #ffffff; }       /* Teal */
    .icon-goai { background: #8b5cf6; color: #ffffff; }        /* Purple */
    .icon-golaporan { background: #475569; color: #ffffff; }   /* Slate */
    .icon-goweb { background: #1e293b; color: #60a5fa; border: 1px solid rgba(255,255,255,0.15); }

    .service-title {
      font-size: 0.72rem;
      font-weight: 700;
      color: #f1f5f9;
      text-align: center;
      line-height: 1.2;
    }

    /* 4. Iklan Slider / Carousel Banner Ala Gojek */
    .gojek-carousel-wrap {
      margin-bottom: 24px;
    }
    .carousel-card {
      position: relative;
      overflow: hidden;
      border-radius: 16px;
    }
    .carousel-slider-track {
      display: flex;
      transition: transform 0.4s ease;
      width: 400%;
    }
    .carousel-slide-item {
      width: 25%;
      padding: 18px 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      text-decoration: none;
      color: #ffffff;
    }
    .c-slide-1 { background: linear-gradient(135deg, #00aa13 0%, #059669 100%); }
    .c-slide-2 { background: linear-gradient(135deg, #ee2737 0%, #b91c1c 100%); }
    .c-slide-3 { background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%); }
    .c-slide-4 { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); }

    .carousel-dot-row {
      display: flex;
      justify-content: center;
      gap: 5px;
      margin-top: 10px;
    }
    .c-dot {
      width: 6px;
      height: 6px;
      border-radius: 3px;
      background: #334155;
      transition: all 0.3s;
    }
    .c-dot.active {
      width: 18px;
      background: #00aa13;
    }

    /* 5. Feed Aktivitas Terkini (Gojek Feed) */
    .gojek-feed-section {
      background: #162033;
      border: 1px solid var(--gojek-border);
      border-radius: 18px;
      padding: 16px;
      margin-bottom: 20px;
    }
    .feed-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
    }
    .feed-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }
    .feed-item:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }

    /* 6. Bottom Navigation Bar Ala Gojek (Dock) */
    .gojek-bottom-nav {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      height: 62px;
      background: rgba(15, 23, 42, 0.95);
      backdrop-filter: blur(16px);
      border-top: 1px solid var(--gojek-border);
      display: flex;
      justify-content: space-around;
      align-items: center;
      z-index: 100;
      max-width: 440px;
      margin: 0 auto;
    }
    .nav-tab {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-decoration: none;
      color: #64748b;
      font-size: 0.65rem;
      font-weight: 700;
      gap: 3px;
    }
    .nav-tab i {
      font-size: 1.3rem;
    }
    .nav-tab.active {
      color: #00aa13;
    }
  </style>
</head>
<body>

<div class="gojek-wrapper">

  <!-- 1. Header & Search Bar Ala Gojek -->
  <header class="gojek-header">
    <a href="index.php" class="gojek-search-pill">
      <i class="bi bi-search" style="color: #64748b; font-size: 1rem;"></i>
      <span>Cari part number, barang, supplier...</span>
    </a>
    <a href="index.php" class="gojek-avatar-btn" title="Mode Web Portal">
      <i class="bi bi-person-fill"></i>
    </a>
  </header>

  <!-- 2. Kartu GoPay Wallet Style (Status Stok & Saldo Item) -->
  <div class="gopay-wallet-card">
    <div class="gopay-left">
      <div class="gopay-title">
        <i class="bi bi-box-seam-fill"></i> STOK GUDANG
      </div>
      <div class="gopay-value"><?= number_format($totalItems) ?> Part</div>
      <div class="gopay-sub">
        <i class="bi bi-check-circle-fill"></i> Realtime Aktif
      </div>
    </div>

    <div class="gopay-actions">
      <!-- Pintasan 1: Masuk -->
      <a href="masuk.php" class="gopay-action-btn">
        <div class="gopay-btn-icon" style="background: rgba(0, 170, 19, 0.15); color: #00aa13; border-color: rgba(0, 170, 19, 0.3);">
          <i class="bi bi-arrow-down-left"></i>
        </div>
        <span>Masuk</span>
      </a>

      <!-- Pintasan 2: Keluar -->
      <a href="keluar.php" class="gopay-action-btn">
        <div class="gopay-btn-icon" style="background: rgba(238, 39, 55, 0.15); color: #ee2737; border-color: rgba(238, 39, 55, 0.3);">
          <i class="bi bi-arrow-up-right"></i>
        </div>
        <span>Keluar</span>
      </a>

      <!-- Pintasan 3: Tanya AI -->
      <a href="tanya_ai.php" class="gopay-action-btn">
        <div class="gopay-btn-icon" style="background: rgba(139, 92, 246, 0.15); color: #c084fc; border-color: rgba(139, 92, 246, 0.3);">
          <i class="bi bi-robot"></i>
        </div>
        <span>Tanya AI</span>
      </a>

      <!-- Pintasan 4: Cetak -->
      <a href="export.php?type=stok_pdf" target="_blank" class="gopay-action-btn">
        <div class="gopay-btn-icon" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24; border-color: rgba(245, 158, 11, 0.3);">
          <i class="bi bi-printer"></i>
        </div>
        <span>Cetak</span>
      </a>
    </div>
  </div>

  <!-- 3. Grid 8 Tombol Ikon Layanan Persis Gojek (GoMasuk, GoKeluar, dll) -->
  <div class="gojek-services-grid">
    <!-- 1. GoMasuk -->
    <a href="masuk.php" class="gojek-service-item">
      <div class="gojek-circle-icon icon-gomasuk">
        <i class="bi bi-box-arrow-in-down"></i>
      </div>
      <div class="service-title">Brg Masuk</div>
    </a>

    <!-- 2. GoKeluar -->
    <a href="keluar.php" class="gojek-service-item">
      <div class="gojek-circle-icon icon-gokeluar">
        <i class="bi bi-box-arrow-up-right"></i>
      </div>
      <div class="service-title">Brg Keluar</div>
    </a>

    <!-- 3. GoBarang -->
    <a href="barang.php" class="gojek-service-item">
      <div class="gojek-circle-icon icon-gobarang">
        <i class="bi bi-boxes"></i>
      </div>
      <div class="service-title">Katalog P/N</div>
    </a>

    <!-- 4. GoSupplier -->
    <a href="supplier.php" class="gojek-service-item">
      <div class="gojek-circle-icon icon-gosupplier">
        <i class="bi bi-truck"></i>
      </div>
      <div class="service-title">Supplier</div>
    </a>

    <!-- 5. GoPIC -->
    <a href="pic.php" class="gojek-service-item">
      <div class="gojek-circle-icon icon-gopic">
        <i class="bi bi-people-fill"></i>
      </div>
      <div class="service-title">Data PIC</div>
    </a>

    <!-- 6. GoAI -->
    <a href="tanya_ai.php" class="gojek-service-item">
      <div class="gojek-circle-icon icon-goai">
        <i class="bi bi-robot"></i>
      </div>
      <div class="service-title">Tanya AI</div>
    </a>

    <!-- 7. GoLaporan -->
    <a href="laporan.php" class="gojek-service-item">
      <div class="gojek-circle-icon icon-golaporan">
        <i class="bi bi-file-earmark-bar-graph"></i>
      </div>
      <div class="service-title">Laporan</div>
    </a>

    <!-- 8. Lainnya / Web Portal -->
    <a href="index.php" class="gojek-service-item">
      <div class="gojek-circle-icon icon-goweb">
        <i class="bi bi-grid-fill"></i>
      </div>
      <div class="service-title">Lainnya</div>
    </a>
  </div>

  <!-- 4. Iklan Slider / Carousel Banner Ala Gojek Promo -->
  <div class="gojek-carousel-wrap">
    <div class="carousel-card">
      <div class="carousel-slider-track" id="cTrack">
        <!-- Banner 1: Masuk -->
        <a href="masuk.php" class="carousel-slide-item c-slide-1">
          <div>
            <div style="font-size: 0.65rem; font-weight: 800; background: rgba(0,0,0,0.2); display: inline-block; padding: 2px 6px; border-radius: 4px; margin-bottom: 4px;">STOCK IN</div>
            <h3 style="font-size: 0.95rem; font-weight: 800; margin-bottom: 2px;">Penerimaan Kiriman</h3>
            <p style="font-size: 0.72rem; opacity: 0.9;">Catat no surat jalan & multi-item cepat.</p>
          </div>
          <i class="bi bi-box-arrow-in-down" style="font-size: 2.2rem; opacity: 0.85;"></i>
        </a>

        <!-- Banner 2: Keluar -->
        <a href="keluar.php" class="carousel-slide-item c-slide-2">
          <div>
            <div style="font-size: 0.65rem; font-weight: 800; background: rgba(0,0,0,0.2); display: inline-block; padding: 2px 6px; border-radius: 4px; margin-bottom: 4px;">STOCK OUT</div>
            <h3 style="font-size: 0.95rem; font-weight: 800; margin-bottom: 2px;">Pengeluaran Tools & Part</h3>
            <p style="font-size: 0.72rem; opacity: 0.9;">Otomatis mengurangi stok fisik.</p>
          </div>
          <i class="bi bi-box-arrow-up-right" style="font-size: 2.2rem; opacity: 0.85;"></i>
        </a>

        <!-- Banner 3: AI -->
        <a href="tanya_ai.php" class="carousel-slide-item c-slide-3">
          <div>
            <div style="font-size: 0.65rem; font-weight: 800; background: rgba(0,0,0,0.2); display: inline-block; padding: 2px 6px; border-radius: 4px; margin-bottom: 4px;">SI-NYA AI</div>
            <h3 style="font-size: 0.95rem; font-weight: 800; margin-bottom: 2px;">Asisten Pintar Gudang</h3>
            <p style="font-size: 0.72rem; opacity: 0.9;">Tanya sisa stok & minta draf laporan.</p>
          </div>
          <i class="bi bi-robot" style="font-size: 2.2rem; opacity: 0.85;"></i>
        </a>

        <!-- Banner 4: Laporan -->
        <a href="laporan.php" class="carousel-slide-item c-slide-4">
          <div>
            <div style="font-size: 0.65rem; font-weight: 800; background: rgba(0,0,0,0.2); display: inline-block; padding: 2px 6px; border-radius: 4px; margin-bottom: 4px;">DOKUMEN</div>
            <h3 style="font-size: 0.95rem; font-weight: 800; margin-bottom: 2px;">Cetak Dokumen Resmi</h3>
            <p style="font-size: 0.72rem; opacity: 0.9;">Format Excel & PDF siap audit kantor.</p>
          </div>
          <i class="bi bi-printer" style="font-size: 2.2rem; opacity: 0.85;"></i>
        </a>
      </div>
    </div>

    <!-- Dots -->
    <div class="carousel-dot-row" id="cDots">
      <div class="c-dot active"></div>
      <div class="c-dot"></div>
      <div class="c-dot"></div>
      <div class="c-dot"></div>
    </div>
  </div>

  <!-- 5. Feed Aktivitas Terakhir (Gojek Feed) -->
  <div class="gojek-feed-section">
    <div class="feed-header">
      <div style="font-size: 0.82rem; font-weight: 800; color: #ffffff;">Aktivitas Terkini Gudang</div>
      <a href="laporan.php" style="font-size: 0.72rem; color: #00aa13; text-decoration: none; font-weight: 700;">Lihat Semua</a>
    </div>

    <?php if (!empty($recentActivity)): ?>
      <?php foreach ($recentActivity as $act): 
        $isIn = $act['tipe'] === 'masuk';
      ?>
        <div class="feed-item">
          <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 32px; height: 32px; border-radius: 10px; background: <?= $isIn ? 'rgba(0, 170, 19, 0.2)' : 'rgba(238, 39, 55, 0.2)' ?>; color: <?= $isIn ? '#00aa13' : '#ee2737' ?>; display: flex; align-items: center; justify-content: center; font-size: 0.95rem;">
              <i class="bi <?= $isIn ? 'bi-box-arrow-in-down' : 'bi-box-arrow-up-right' ?>"></i>
            </div>
            <div>
              <div style="font-size: 0.78rem; font-weight: 700; color: #ffffff;"><?= htmlspecialchars($act['pihak']) ?></div>
              <div style="font-size: 0.68rem; color: #94a3b8;"><?= htmlspecialchars($act['no_trx']) ?> • <?= date('d M', strtotime($act['tgl'])) ?></div>
            </div>
          </div>
          <div style="font-size: 0.82rem; font-weight: 800; color: <?= $isIn ? '#34d399' : '#f87171' ?>;">
            <?= $isIn ? '+' : '-' ?><?= formatStok($act['qty']) ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="font-size: 0.75rem; color: #64748b; text-align: center; padding: 10px 0;">Belum ada riwayat transaksi.</p>
    <?php endif; ?>
  </div>

</div>

<!-- 6. Bottom Navigation Bar Ala Gojek (Dock Bawah) -->
<nav class="gojek-bottom-nav">
  <a href="app.php" class="nav-tab active">
    <i class="bi bi-house-door-fill"></i>
    <span>Beranda</span>
  </a>
  <a href="masuk.php" class="nav-tab">
    <i class="bi bi-box-arrow-in-down"></i>
    <span>Masuk</span>
  </a>
  <a href="tanya_ai.php" class="nav-tab">
    <i class="bi bi-robot"></i>
    <span>Tanya AI</span>
  </a>
  <a href="keluar.php" class="nav-tab">
    <i class="bi bi-box-arrow-up-right"></i>
    <span>Keluar</span>
  </a>
  <a href="index.php" class="nav-tab">
    <i class="bi bi-grid-1x2-fill"></i>
    <span>Web</span>
  </a>
</nav>

<script>
// Auto Carousel Banner Slider Ala Gojek Promo
document.addEventListener('DOMContentLoaded', () => {
  const track = document.getElementById('cTrack');
  const dots = document.querySelectorAll('.c-dot');
  let current = 0;
  const total = 4;

  function setSlide(idx) {
    current = idx;
    track.style.transform = `translateX(-${current * 25}%)`;
    dots.forEach((d, i) => d.classList.toggle('active', i === current));
  }

  setInterval(() => {
    let next = (current + 1) % total;
    setSlide(next);
  }, 4000);
});
</script>

</body>
</html>
