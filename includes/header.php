<?php
// includes/header.php
require_once __DIR__ . '/../config/database.php';

$appName = getSetting('nama_aplikasi', 'MaoneArt Stock & Inventory');
$namaGudang = getSetting('nama_gudang', 'Gudang Pusat');
$currPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' . htmlspecialchars($appName) : htmlspecialchars($appName) ?></title>

  <!-- Google Fonts & Bootstrap Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <!-- PWA & Mobile Icons -->
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#0f172a">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>📦</text></svg>">

  <!-- Main Stylesheet -->
  <link rel="stylesheet" href="assets/css/style.css">

  <!-- Instant Theme Initializer (Prevent Flash of Dark/Light) -->
  <script>
    (function() {
      const savedTheme = localStorage.getItem('maoneart_theme') || 'dark';
      document.documentElement.setAttribute('data-theme', savedTheme);
      if (savedTheme === 'light') {
        document.documentElement.classList.add('theme-light');
      }
    })();
  </script>
</head>
<body>

<!-- Backdrop Overlay Sidebar -->
<div id="sidebarBackdrop" class="sidebar-backdrop"></div>

<!-- Slide-Left Sidebar Drawer -->
<aside id="sidebarDrawer" class="sidebar-drawer">
  <!-- Sidebar Header -->
  <div class="sidebar-header">
    <div style="display: flex; align-items: center; gap: 10px;">
      <div class="brand-icon" style="width: 34px; height: 34px; font-size: 1.1rem;">
        <i class="bi bi-box-seam-fill"></i>
      </div>
      <div>
        <div style="font-weight: 800; font-size: 0.95rem; color: var(--text-main); letter-spacing: -0.01em;">MaoneArt Gudang</div>
        <div style="font-size: 0.68rem; color: #60a5fa;"><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($namaGudang) ?></div>
      </div>
    </div>
    <button type="button" id="btnCloseSidebar" class="sidebar-close-btn" title="Tutup Menu">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>

  <!-- Sidebar Navigation Body -->
  <div class="sidebar-body">
    <!-- Group 1: Dashboard -->
    <div>
      <div class="sidebar-section-title">Dashboard & Monitoring</div>
      <a href="index.php" class="sidebar-link <?= in_array($currPage, ['index', '']) ? 'active' : '' ?>">
        <i class="bi bi-grid-1x2-fill text-primary"></i>
        <span>Aktual Stok Realtime</span>
      </a>
    </div>

    <!-- Group 2: Transaksi Gudang -->
    <div>
      <div class="sidebar-section-title">Arus Transaksi</div>
      <a href="masuk.php" class="sidebar-link <?= $currPage === 'masuk' ? 'active' : '' ?>">
        <i class="bi bi-box-arrow-in-down text-success"></i>
        <span>Input Barang Masuk</span>
      </a>
      <a href="keluar.php" class="sidebar-link <?= $currPage === 'keluar' ? 'active' : '' ?>">
        <i class="bi bi-box-arrow-up-right text-danger"></i>
        <span>Input Barang Keluar</span>
      </a>
    </div>

    <!-- Group 3: Master Data -->
    <div>
      <div class="sidebar-section-title">Master Data & Supplier</div>
      <a href="barang.php" class="sidebar-link <?= $currPage === 'barang' ? 'active' : '' ?>">
        <i class="bi bi-boxes text-info"></i>
        <span>Data Barang</span>
      </a>
      <a href="supplier.php" class="sidebar-link <?= $currPage === 'supplier' ? 'active' : '' ?>">
        <i class="bi bi-truck text-warning"></i>
        <span>Data Rekanan Supplier</span>
      </a>
      <a href="pic.php" class="sidebar-link <?= $currPage === 'pic' ? 'active' : '' ?>">
        <i class="bi bi-people-fill text-purple"></i>
        <span>Data PIC / Peminta Tools</span>
      </a>
    </div>

    <!-- Group 4: Laporan & AI -->
    <div>
      <div class="sidebar-section-title">Laporan & Kecerdasan Buatan</div>
      <a href="laporan.php" class="sidebar-link <?= $currPage === 'laporan' ? 'active' : '' ?>">
        <i class="bi bi-file-earmark-text-fill text-primary"></i>
        <span>Rekapitulasi Mutasi</span>
      </a>
      <a href="tanya_ai.php" class="sidebar-link highlight <?= $currPage === 'tanya_ai' ? 'active' : '' ?>">
        <i class="bi bi-robot"></i>
        <span>Tanya Si-nya (AI Assistant)</span>
      </a>
    </div>

    <!-- Group 5: Pengaturan & APK -->
    <div>
      <div class="sidebar-section-title">Sistem & Pengaturan</div>
      <a href="pengaturan.php" class="sidebar-link <?= $currPage === 'pengaturan' ? 'active' : '' ?>">
        <i class="bi bi-gear-fill text-warning"></i>
        <span>Pengaturan Sistem</span>
      </a>
      <a href="app.php" class="sidebar-link <?= $currPage === 'app' ? 'active' : '' ?>">
        <i class="bi bi-phone-fill text-info"></i>
        <span>Mode Khusus APK HP</span>
      </a>
    </div>
  </div>

  <!-- Sidebar Footer -->
  <div class="sidebar-footer">
    <span>v2.0.0 Enterprise</span>
    <span style="color: #34d399;"><i class="bi bi-wifi"></i> Online Local</span>
  </div>
</aside>

<!-- Top Glass Navbar -->
<header class="top-navbar">
  <div class="top-nav-inner">
    <div style="display: flex; align-items: center; gap: 12px;">
      <!-- Hamburger Button (Pemicu Slide Kiri) -->
      <button type="button" id="btnOpenSidebar" class="btn-hamburger" title="Buka Menu Navigasi">
        <i class="bi bi-list"></i>
      </button>

      <a href="index.php" class="brand-badge">
        <div class="brand-icon">
          <i class="bi bi-box-seam-fill"></i>
        </div>
        <div class="brand-text">
          <h1><?= htmlspecialchars($appName) ?></h1>
          <p><i class="bi bi-geo-alt-fill text-primary"></i> <?= htmlspecialchars($namaGudang) ?></p>
        </div>
      </a>
    </div>

    <!-- Desktop Menu Shortcut Pills -->
    <nav class="desktop-menu">
      <a href="index.php" class="nav-pill <?= in_array($currPage, ['index', '']) ? 'active' : '' ?>">
        <i class="bi bi-grid-1x2-fill"></i> Home
      </a>
      <a href="masuk.php" class="nav-pill <?= $currPage === 'masuk' ? 'active' : '' ?>">
        <i class="bi bi-box-arrow-in-down text-success"></i> Masuk
      </a>
      <a href="keluar.php" class="nav-pill <?= $currPage === 'keluar' ? 'active' : '' ?>">
        <i class="bi bi-box-arrow-up-right text-danger"></i> Keluar
      </a>
      <a href="barang.php" class="nav-pill <?= $currPage === 'barang' ? 'active' : '' ?>">
        <i class="bi bi-boxes text-info"></i> Barang
      </a>
      <a href="supplier.php" class="nav-pill <?= $currPage === 'supplier' ? 'active' : '' ?>">
        <i class="bi bi-truck text-warning"></i> Supplier
      </a>
      <a href="pic.php" class="nav-pill <?= $currPage === 'pic' ? 'active' : '' ?>">
        <i class="bi bi-people-fill text-purple"></i> PIC
      </a>
      <a href="pengaturan.php" class="nav-pill <?= $currPage === 'pengaturan' ? 'active' : '' ?>">
        <i class="bi bi-gear-fill"></i> Pengaturan
      </a>
    </nav>

    <!-- Quick Action / AI Button -->
    <div style="display: flex; align-items: center; gap: 8px;">
      <a href="app.php" class="btn btn-secondary btn-sm" title="Mode APK Petugas">
        <i class="bi bi-phone-fill text-info"></i> <span style="display: inline-block;">APK</span>
      </a>
      <a href="tanya_ai.php" class="btn btn-primary btn-sm" style="background: linear-gradient(135deg, #8b5cf6, #6d28d9); color: #ffffff !important;">
        <i class="bi bi-robot" style="color: #ffffff !important;"></i> <span style="color: #ffffff !important;">Tanya AI</span>
      </a>
    </div>
  </div>
</header>

<main class="main-wrapper">
