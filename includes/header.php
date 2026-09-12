<?php
// includes/header.php
require_once __DIR__ . '/../config/database.php';

$appName = getSetting('nama_aplikasi', 'MaoneArt Stock & Inventory');
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
</head>
<body>

<!-- Top Glass Navbar -->
<header class="top-navbar">
  <div class="top-nav-inner">
    <a href="index.php" class="brand-badge">
      <div class="brand-icon">
        <i class="bi bi-box-seam-fill"></i>
      </div>
      <div class="brand-text">
        <h1><?= htmlspecialchars($appName) ?></h1>
        <p><i class="bi bi-geo-alt-fill text-primary"></i> <?= htmlspecialchars(getSetting('nama_gudang', 'Gudang Pusat')) ?></p>
      </div>
    </a>

    <!-- Desktop Navigation Menu -->
    <nav class="desktop-menu">
      <a href="index.php" class="nav-pill <?= in_array($currPage, ['index', '']) ? 'active' : '' ?>">
        <i class="bi bi-grid-1x2-fill"></i> Monitoring
      </a>
      <a href="masuk.php" class="nav-pill <?= $currPage === 'masuk' ? 'active' : '' ?>">
        <i class="bi bi-box-arrow-in-down"></i> Barang Masuk
      </a>
      <a href="keluar.php" class="nav-pill <?= $currPage === 'keluar' ? 'active' : '' ?>">
        <i class="bi bi-box-arrow-up-right"></i> Barang Keluar
      </a>
      <a href="barang.php" class="nav-pill <?= $currPage === 'barang' ? 'active' : '' ?>">
        <i class="bi bi-boxes"></i> Data Barang
      </a>
      <a href="supplier.php" class="nav-pill <?= $currPage === 'supplier' ? 'active' : '' ?>">
        <i class="bi bi-truck"></i> Supplier
      </a>
      <a href="pic.php" class="nav-pill <?= $currPage === 'pic' ? 'active' : '' ?>">
        <i class="bi bi-people-fill"></i> Data PIC
      </a>
      <a href="laporan.php" class="nav-pill <?= $currPage === 'laporan' ? 'active' : '' ?>">
        <i class="bi bi-file-earmark-text-fill"></i> Laporan
      </a>
      <a href="tanya_ai.php" class="nav-pill <?= $currPage === 'tanya_ai' ? 'active' : '' ?>" style="background: rgba(139, 92, 246, 0.2); border: 1px solid rgba(139, 92, 246, 0.4); color: #c084fc;">
        <i class="bi bi-robot"></i> Tanya Si-nya
      </a>
    </nav>

    <!-- User Action / Quick Links -->
    <div style="display: flex; align-items: center; gap: 8px;">
      <a href="app.php" class="btn btn-secondary btn-sm" title="Mode APK Input Lapangan">
        <i class="bi bi-phone-fill text-info"></i> Mode APK
      </a>
      <a href="tanya_ai.php" class="btn btn-primary btn-sm" style="background: linear-gradient(135deg, #8b5cf6, #6d28d9);">
        <i class="bi bi-stars"></i> Tanya AI
      </a>
    </div>
  </div>
</header>

<main class="main-wrapper">
