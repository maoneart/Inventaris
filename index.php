<?php
// index.php - Dashboard Responsive: Gojek Superapp Style di HP & Enterprise Desktop di PC
$pageTitle = "Monitoring Stok Realtime";
require_once __DIR__ . '/includes/header.php';

// Ambil Statistik
try {
    // 1. Total Jenis Barang
    $stmt1 = $pdo->query("SELECT COUNT(*) FROM barang");
    $totalBarang = $stmt1->fetchColumn();

    // 2. Total Stok Kritis / Menipis
    $stmt2 = $pdo->query("SELECT COUNT(*) FROM barang WHERE stok_saat_ini <= stok_minimum");
    $totalKritis = $stmt2->fetchColumn();

    // 3. Total Masuk Bulan Ini
    $stmt3 = $pdo->query("SELECT COALESCE(SUM(total_qty), 0) FROM transaksi_masuk WHERE MONTH(tanggal_masuk) = MONTH(CURRENT_DATE()) AND YEAR(tanggal_masuk) = YEAR(CURRENT_DATE())");
    $masukBulanIni = $stmt3->fetchColumn();

    // 4. Total Keluar Bulan Ini
    $stmt4 = $pdo->query("SELECT COALESCE(SUM(total_qty), 0) FROM transaksi_keluar WHERE MONTH(tanggal_keluar) = MONTH(CURRENT_DATE()) AND YEAR(tanggal_keluar) = YEAR(CURRENT_DATE())");
    $keluarBulanIni = $stmt4->fetchColumn();

    // 5. Data Barang dengan Join Kategori, Satuan, & Supplier
    $stmtBarang = $pdo->query("
        SELECT b.*, k.nama_kategori, s.nama_satuan, s.singkatan, sup.nama_supplier
        FROM barang b
        LEFT JOIN kategori k ON b.id_kategori = k.id
        LEFT JOIN satuan s ON b.id_satuan = s.id
        LEFT JOIN supplier sup ON b.id_supplier = sup.id
        ORDER BY (b.stok_saat_ini <= b.stok_minimum) DESC, b.nama_barang ASC
    ");
    $listBarang = $stmtBarang->fetchAll();

    // 6. Transaksi Terbaru
    $stmtRecentIn = $pdo->query("
        SELECT tm.*, sup.nama_supplier 
        FROM transaksi_masuk tm
        JOIN supplier sup ON tm.id_supplier = sup.id
        ORDER BY tm.created_at DESC LIMIT 4
    ");
    $recentIn = $stmtRecentIn->fetchAll();

    $stmtRecentOut = $pdo->query("
        SELECT tk.*, p.nama_pic, p.departemen
        FROM transaksi_keluar tk
        JOIN pic p ON tk.id_pic = p.id
        ORDER BY tk.created_at DESC LIMIT 4
    ");
    $recentOut = $stmtRecentOut->fetchAll();

    // 7. Feed Gabungan untuk HP (Gojek Feed)
    $stmtFeed = $pdo->query("
        (SELECT 'masuk' as tipe, tm.no_masuk as no_trx, s.nama_supplier as pihak, tm.total_qty as qty, tm.tanggal_masuk as tgl, tm.created_at
         FROM transaksi_masuk tm JOIN supplier s ON tm.id_supplier = s.id)
        UNION ALL
        (SELECT 'keluar' as tipe, tk.no_keluar as no_trx, p.nama_pic as pihak, tk.total_qty as qty, tk.tanggal_keluar as tgl, tk.created_at
         FROM transaksi_keluar tk JOIN pic p ON tk.id_pic = p.id)
        ORDER BY created_at DESC LIMIT 4
    ");
    $feedActivities = $stmtFeed->fetchAll();

} catch (Exception $e) {
    $errorMsg = $e->getMessage();
}
?>

<!-- =========================================================================
     BAGIAN 1: TAMPILAN KHUSUS LAYAR HP / SMARTPHONE (GOJEK SUPERAPP STYLE)
     ========================================================================= -->
<div class="mobile-gojek-view" style="display: none;">
  <div class="gojek-wrapper">

    <!-- 1. Header & Search Bar Ala Gojek -->
    <div class="gojek-header-fixed">
      <a href="barang.php" class="gojek-search-pill-web">
        <i class="bi bi-search" style="color: #64748b; font-size: 1rem;"></i>
        <span>Cari part number, barang, supplier...</span>
      </a>
      <a href="tanya_ai.php" class="gojek-avatar-btn-web" title="Tanya AI">
        <i class="bi bi-robot"></i>
      </a>
    </div>

    <!-- Body Content with Edge-to-Edge Fluid Padding -->
    <div class="gojek-body-content">

      <!-- 2. Kartu Saldo / GoPay Wallet Style -->
      <div class="gopay-wallet-card-web">
      <div style="display: flex; flex-direction: column; gap: 2px; border-right: 1px solid var(--card-border); padding-right: 14px; min-width: 125px;">
        <div style="font-size: 0.7rem; font-weight: 800; color: #2563eb; display: flex; align-items: center; gap: 4px; letter-spacing: 0.04em;">
          <i class="bi bi-box-seam-fill"></i> STOK GUDANG
        </div>
        <div style="font-size: 1.15rem; font-weight: 800; color: var(--text-main);"><?= number_format($totalBarang) ?> Part</div>
        <div style="font-size: 0.65rem; color: #059669; font-weight: 700;">
          <i class="bi bi-check-circle-fill"></i> Realtime Aktif
        </div>
      </div>

      <div style="display: flex; gap: 10px; flex: 1; justify-content: space-around; padding-left: 6px;">
        <a href="masuk.php" style="display: flex; flex-direction: column; align-items: center; text-decoration: none; gap: 4px;">
          <div style="width: 34px; height: 34px; border-radius: 10px; background: rgba(0, 170, 19, 0.15); border: 1px solid rgba(0, 170, 19, 0.35); display: flex; align-items: center; justify-content: center; font-size: 1rem; color: #00aa13;">
            <i class="bi bi-arrow-down-left"></i>
          </div>
          <span style="font-size: 0.65rem; font-weight: 700; color: var(--text-muted);">Masuk</span>
        </a>

        <a href="keluar.php" style="display: flex; flex-direction: column; align-items: center; text-decoration: none; gap: 4px;">
          <div style="width: 34px; height: 34px; border-radius: 10px; background: rgba(238, 39, 55, 0.15); border: 1px solid rgba(238, 39, 55, 0.35); display: flex; align-items: center; justify-content: center; font-size: 1rem; color: #ee2737;">
            <i class="bi bi-arrow-up-right"></i>
          </div>
          <span style="font-size: 0.65rem; font-weight: 700; color: var(--text-muted);">Keluar</span>
        </a>

        <a href="tanya_ai.php" style="display: flex; flex-direction: column; align-items: center; text-decoration: none; gap: 4px;">
          <div style="width: 34px; height: 34px; border-radius: 10px; background: rgba(139, 92, 246, 0.15); border: 1px solid rgba(139, 92, 246, 0.35); display: flex; align-items: center; justify-content: center; font-size: 1rem; color: #8b5cf6;">
            <i class="bi bi-robot"></i>
          </div>
          <span style="font-size: 0.65rem; font-weight: 700; color: var(--text-muted);">Tanya AI</span>
        </a>

        <a href="export.php?type=stok_pdf" target="_blank" style="display: flex; flex-direction: column; align-items: center; text-decoration: none; gap: 4px;">
          <div style="width: 34px; height: 34px; border-radius: 10px; background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.35); display: flex; align-items: center; justify-content: center; font-size: 1rem; color: #d97706;">
            <i class="bi bi-printer"></i>
          </div>
          <span style="font-size: 0.65rem; font-weight: 700; color: var(--text-muted);">Cetak</span>
        </a>
      </div>
    </div>

    <!-- 3. Grid 8 Tombol Ikon Layanan Persis Gojek -->
    <div class="gojek-services-grid-web">
      <!-- 1. GoMasuk -->
      <a href="masuk.php" class="gojek-service-item-web" style="display: flex; flex-direction: column; align-items: center; text-decoration: none; gap: 6px;">
        <div class="gojek-circle-icon-web" style="background: #00aa13; color: #ffffff;">
          <i class="bi bi-box-arrow-in-down"></i>
        </div>
        <div class="service-title" style="font-size: 0.72rem; font-weight: 700; color: var(--text-main); text-align: center;">Brg Masuk</div>
      </a>

      <!-- 2. GoKeluar -->
      <a href="keluar.php" class="gojek-service-item-web" style="display: flex; flex-direction: column; align-items: center; text-decoration: none; gap: 6px;">
        <div class="gojek-circle-icon-web" style="background: #ee2737; color: #ffffff;">
          <i class="bi bi-box-arrow-up-right"></i>
        </div>
        <div class="service-title" style="font-size: 0.72rem; font-weight: 700; color: var(--text-main); text-align: center;">Brg Keluar</div>
      </a>

      <!-- 3. GoBarang -->
      <a href="barang.php" class="gojek-service-item-web" style="display: flex; flex-direction: column; align-items: center; text-decoration: none; gap: 6px;">
        <div class="gojek-circle-icon-web" style="background: #0081a0; color: #ffffff;">
          <i class="bi bi-boxes"></i>
        </div>
        <div class="service-title" style="font-size: 0.72rem; font-weight: 700; color: var(--text-main); text-align: center;">Barang</div>
      </a>

      <!-- 4. GoSupplier -->
      <a href="supplier.php" class="gojek-service-item-web" style="display: flex; flex-direction: column; align-items: center; text-decoration: none; gap: 6px;">
        <div class="gojek-circle-icon-web" style="background: #df6b00; color: #ffffff;">
          <i class="bi bi-truck"></i>
        </div>
        <div class="service-title" style="font-size: 0.72rem; font-weight: 700; color: var(--text-main); text-align: center;">Supplier</div>
      </a>

      <!-- 5. GoPIC -->
      <a href="pic.php" class="gojek-service-item-web" style="display: flex; flex-direction: column; align-items: center; text-decoration: none; gap: 6px;">
        <div class="gojek-circle-icon-web" style="background: #00a3a6; color: #ffffff;">
          <i class="bi bi-people-fill"></i>
        </div>
        <div class="service-title" style="font-size: 0.72rem; font-weight: 700; color: var(--text-main); text-align: center;">Data PIC</div>
      </a>

      <!-- 6. GoAI -->
      <a href="tanya_ai.php" class="gojek-service-item-web" style="display: flex; flex-direction: column; align-items: center; text-decoration: none; gap: 6px;">
        <div class="gojek-circle-icon-web" style="background: #8b5cf6; color: #ffffff;">
          <i class="bi bi-robot"></i>
        </div>
        <div class="service-title" style="font-size: 0.72rem; font-weight: 700; color: var(--text-main); text-align: center;">Tanya AI</div>
      </a>

      <!-- 7. GoLaporan -->
      <a href="laporan.php" class="gojek-service-item-web" style="display: flex; flex-direction: column; align-items: center; text-decoration: none; gap: 6px;">
        <div class="gojek-circle-icon-web" style="background: #475569; color: #ffffff;">
          <i class="bi bi-file-earmark-bar-graph"></i>
        </div>
        <div class="service-title" style="font-size: 0.72rem; font-weight: 700; color: var(--text-main); text-align: center;">Laporan</div>
      </a>

      <!-- 8. Dokumen Ekspor -->
      <a href="export.php?type=stok_excel" class="gojek-service-item-web" style="display: flex; flex-direction: column; align-items: center; text-decoration: none; gap: 6px;">
        <div class="gojek-circle-icon-web" style="background: #1e293b; color: #34d399; border: 1px solid rgba(255,255,255,0.15);">
          <i class="bi bi-file-earmark-excel"></i>
        </div>
        <div class="service-title" style="font-size: 0.72rem; font-weight: 700; color: var(--text-main); text-align: center;">Ekspor XLS</div>
      </a>
    </div>

    <!-- 4. Carousel Banner Slider Promo Ala Gojek -->
    <div style="margin-bottom: 24px;">
      <div style="overflow: hidden; border-radius: 16px; box-shadow: var(--shadow-card);">
        <div id="mTrack" style="display: flex; transition: transform 0.4s ease; width: 400%;">
          <!-- Slide 1 -->
          <a href="masuk.php" style="width: 25%; padding: 18px 16px; background: linear-gradient(135deg, #00aa13 0%, #059669 100%); display: flex; align-items: center; justify-content: space-between; text-decoration: none; color: #ffffff;">
            <div>
              <div style="font-size: 0.65rem; font-weight: 800; background: rgba(0,0,0,0.2); display: inline-block; padding: 2px 6px; border-radius: 4px; margin-bottom: 4px;">STOCK IN</div>
              <h3 style="font-size: 0.95rem; font-weight: 800; margin-bottom: 2px; color: #ffffff;">Penerimaan Kiriman</h3>
              <p style="font-size: 0.72rem; opacity: 0.9; color: #ffffff;">Catat no surat jalan & multi-item cepat.</p>
            </div>
            <i class="bi bi-box-arrow-in-down" style="font-size: 2.2rem; opacity: 0.85; color: #ffffff;"></i>
          </a>

          <!-- Slide 2 -->
          <a href="keluar.php" style="width: 25%; padding: 18px 16px; background: linear-gradient(135deg, #ee2737 0%, #b91c1c 100%); display: flex; align-items: center; justify-content: space-between; text-decoration: none; color: #ffffff;">
            <div>
              <div style="font-size: 0.65rem; font-weight: 800; background: rgba(0,0,0,0.2); display: inline-block; padding: 2px 6px; border-radius: 4px; margin-bottom: 4px;">STOCK OUT</div>
              <h3 style="font-size: 0.95rem; font-weight: 800; margin-bottom: 2px; color: #ffffff;">Pengeluaran Tools & Part</h3>
              <p style="font-size: 0.72rem; opacity: 0.9; color: #ffffff;">Otomatis mengurangi stok fisik.</p>
            </div>
            <i class="bi bi-box-arrow-up-right" style="font-size: 2.2rem; opacity: 0.85; color: #ffffff;"></i>
          </a>

          <!-- Slide 3 -->
          <a href="tanya_ai.php" style="width: 25%; padding: 18px 16px; background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%); display: flex; align-items: center; justify-content: space-between; text-decoration: none; color: #ffffff;">
            <div>
              <div style="font-size: 0.65rem; font-weight: 800; background: rgba(0,0,0,0.2); display: inline-block; padding: 2px 6px; border-radius: 4px; margin-bottom: 4px;">SI-NYA AI</div>
              <h3 style="font-size: 0.95rem; font-weight: 800; margin-bottom: 2px; color: #ffffff;">Asisten Pintar Gudang</h3>
              <p style="font-size: 0.72rem; opacity: 0.9; color: #ffffff;">Tanya sisa stok & minta draf laporan.</p>
            </div>
            <i class="bi bi-robot" style="font-size: 2.2rem; opacity: 0.85; color: #ffffff;"></i>
          </a>

          <!-- Slide 4 -->
          <a href="laporan.php" style="width: 25%; padding: 18px 16px; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); display: flex; align-items: center; justify-content: space-between; text-decoration: none; color: #ffffff;">
            <div>
              <div style="font-size: 0.65rem; font-weight: 800; background: rgba(0,0,0,0.2); display: inline-block; padding: 2px 6px; border-radius: 4px; margin-bottom: 4px;">DOKUMEN</div>
              <h3 style="font-size: 0.95rem; font-weight: 800; margin-bottom: 2px; color: #ffffff;">Cetak Dokumen Resmi</h3>
              <p style="font-size: 0.72rem; opacity: 0.9; color: #ffffff;">Format Excel & PDF siap audit kantor.</p>
            </div>
            <i class="bi bi-printer" style="font-size: 2.2rem; opacity: 0.85; color: #ffffff;"></i>
          </a>
        </div>
      </div>

      <div id="mDots" style="display: flex; justify-content: center; gap: 5px; margin-top: 10px;">
        <div class="c-dot active"></div>
        <div class="c-dot"></div>
        <div class="c-dot"></div>
        <div class="c-dot"></div>
      </div>
    </div>

    <!-- 5. Feed Aktivitas Terkini (Gojek Feed) -->
    <div class="glass-card" style="border-radius: 18px; padding: 16px; margin-bottom: 20px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <div style="font-size: 0.82rem; font-weight: 800; color: var(--text-main);">Aktivitas Terkini Gudang</div>
        <a href="laporan.php" style="font-size: 0.72rem; color: #00aa13; text-decoration: none; font-weight: 700;">Lihat Semua</a>
      </div>

      <?php if (!empty($feedActivities)): ?>
        <?php foreach ($feedActivities as $act): 
          $isIn = $act['tipe'] === 'masuk';
        ?>
          <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--card-border);">
            <div style="display: flex; align-items: center; gap: 10px;">
              <div style="width: 32px; height: 32px; border-radius: 10px; background: <?= $isIn ? 'rgba(0, 170, 19, 0.15)' : 'rgba(238, 39, 55, 0.15)' ?>; color: <?= $isIn ? '#00aa13' : '#ee2737' ?>; display: flex; align-items: center; justify-content: center; font-size: 0.95rem;">
                <i class="bi <?= $isIn ? 'bi-box-arrow-in-down' : 'bi-box-arrow-up-right' ?>"></i>
              </div>
              <div>
                <div style="font-size: 0.78rem; font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($act['pihak']) ?></div>
                <div style="font-size: 0.68rem; color: var(--text-muted);"><?= htmlspecialchars($act['no_trx']) ?> • <?= date('d M', strtotime($act['tgl'])) ?></div>
              </div>
            </div>
            <div style="font-size: 0.82rem; font-weight: 800; color: <?= $isIn ? '#059669' : '#dc2626' ?>;">
              <?= $isIn ? '+' : '-' ?><?= formatStok($act['qty']) ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="font-size: 0.75rem; color: var(--text-muted); text-align: center; padding: 10px 0;">Belum ada riwayat transaksi.</p>
      <?php endif; ?>
    </div>

    </div> <!-- End .gojek-body-content -->
  </div>
</div>

<!-- =========================================================================
     BAGIAN 2: TAMPILAN KHUSUS MONITOR KOMPUTER / DESKTOP (ENTERPRISE DASHBOARD)
     ========================================================================= -->
<div class="desktop-view-only">

  <!-- Quick Action Banners Desktop -->
  <div class="action-grid">
    <a href="masuk.php" class="action-card in">
      <div class="action-card-icon">
        <i class="bi bi-box-arrow-in-down"></i>
      </div>
      <div class="action-card-text">
        <h3>+ Input Barang Masuk</h3>
        <p>Penerimaan dari Supplier & No. Surat Jalan</p>
      </div>
    </a>

    <a href="keluar.php" class="action-card out">
      <div class="action-card-icon">
        <i class="bi bi-box-arrow-up-right"></i>
      </div>
      <div class="action-card-text">
        <h3>- Input Barang Keluar</h3>
        <p>Pengambilan oleh PIC / Teknisi & Divisi</p>
      </div>
    </a>

    <a href="tanya_ai.php" class="action-card ai">
      <div class="action-card-icon">
        <i class="bi bi-robot"></i>
      </div>
      <div class="action-card-text">
        <h3>🤖 Tanya Si-nya (AI)</h3>
        <p>Cek stok natural & buat laporan otomatis</p>
      </div>
    </a>
  </div>

  <!-- Stats Counters Desktop -->
  <div class="stats-grid">
    <div class="stat-box">
      <div class="stat-icon blue">
        <i class="bi bi-boxes"></i>
      </div>
      <div class="stat-info">
        <div class="stat-label">Jenis Barang</div>
        <div class="stat-value"><?= number_format($totalBarang) ?></div>
      </div>
    </div>

    <div class="stat-box">
      <div class="stat-icon <?= $totalKritis > 0 ? 'red' : 'green' ?>">
        <i class="bi <?= $totalKritis > 0 ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' ?>"></i>
      </div>
      <div class="stat-info">
        <div class="stat-label">Stok Kritis / Min</div>
        <div class="stat-value" style="<?= $totalKritis > 0 ? 'color: #f87171;' : '' ?>"><?= number_format($totalKritis) ?></div>
      </div>
    </div>

    <div class="stat-box">
      <div class="stat-icon green">
        <i class="bi bi-arrow-down-left"></i>
      </div>
      <div class="stat-info">
        <div class="stat-label">Masuk (Bln Ini)</div>
        <div class="stat-value"><?= formatStok($masukBulanIni) ?></div>
      </div>
    </div>

    <div class="stat-box">
      <div class="stat-icon orange">
        <i class="bi bi-arrow-up-right"></i>
      </div>
      <div class="stat-info">
        <div class="stat-label">Keluar (Bln Ini)</div>
        <div class="stat-value"><?= formatStok($keluarBulanIni) ?></div>
      </div>
    </div>
  </div>

  <!-- Tabel Monitoring Aktual Stok Realtime Desktop -->
  <div class="glass-card">
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px;">
      <div>
        <h2 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
          <i class="bi bi-broadcast text-primary"></i> Aktual Stok Realtime & Part Number
        </h2>
        <p style="font-size: 0.75rem; color: var(--text-muted);">Status stok fisik langsung terupdate tiap kali ada mutasi masuk/keluar</p>
      </div>
      <div style="display: flex; gap: 8px; flex: 1; max-width: 400px; flex-wrap: wrap;">
        <input type="text" id="tableSearchInput" class="form-control" placeholder="🔍 Cari nama / P/N / supplier / rak..." style="padding: 8px 12px; font-size: 0.82rem; flex: 1; min-width: 160px;">
        <a href="export.php?type=stok_excel" class="btn btn-secondary btn-sm" title="Download Excel">
          <i class="bi bi-file-earmark-excel-fill text-success"></i> Excel
        </a>
        <a href="export.php?type=stok_pdf" target="_blank" class="btn btn-secondary btn-sm" title="Download PDF">
          <i class="bi bi-file-earmark-pdf-fill text-danger"></i> PDF
        </a>
      </div>
    </div>

    <div class="table-responsive">
      <table class="modern-table">
        <thead>
          <tr>
            <th>Kode & Part Number</th>
            <th>Nama Barang</th>
            <th>Supplier Rekanan</th>
            <th>Kategori</th>
            <th>Lokasi Rak</th>
            <th style="text-align: right;">Stok Aktual</th>
            <th style="text-align: right;">Stok Min</th>
            <th style="text-align: center;">Status</th>
            <th style="text-align: center;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($listBarang)): ?>
            <?php foreach ($listBarang as $b): 
              $isCritical = $b['stok_saat_ini'] <= $b['stok_minimum'];
              $isZero = $b['stok_saat_ini'] <= 0;
            ?>
              <tr>
                <td>
                  <strong class="text-primary"><?= htmlspecialchars($b['kode_barang']) ?></strong>
                  <?php if ($b['part_number']): ?>
                    <div style="font-size: 0.75rem; color: #2563eb; font-weight: 700;"><i class="bi bi-tag-fill"></i> <?= htmlspecialchars($b['part_number']) ?></div>
                  <?php endif; ?>
                </td>
                <td>
                  <div style="font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($b['nama_barang']) ?></div>
                  <?php if ($b['spesifikasi']): ?>
                    <div style="font-size: 0.72rem; color: var(--text-muted); max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($b['spesifikasi']) ?></div>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($b['nama_supplier']): ?>
                    <span class="badge badge-success"><i class="bi bi-truck"></i> <?= htmlspecialchars($b['nama_supplier']) ?></span>
                  <?php else: ?>
                    <span style="font-size: 0.72rem; color: var(--text-dim);">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge badge-purple"><?= htmlspecialchars($b['nama_kategori'] ?? 'Umum') ?></span>
                </td>
                <td>
                  <span style="font-size: 0.8rem; color: var(--text-muted);"><i class="bi bi-archive-fill text-dim"></i> <?= htmlspecialchars($b['lokasi_rak'] ?? '-') ?></span>
                </td>
                <td style="text-align: right; font-weight: 800; font-size: 0.95rem; <?= $isCritical ? 'color: #dc2626;' : 'color: #059669;' ?>">
                  <?= formatStok($b['stok_saat_ini']) ?> <span style="font-size: 0.72rem; font-weight: 600; color: var(--text-muted);"><?= htmlspecialchars($b['singkatan'] ?? 'pcs') ?></span>
                </td>
                <td style="text-align: right; font-size: 0.82rem; color: var(--text-muted);">
                  <?= formatStok($b['stok_minimum']) ?> <?= htmlspecialchars($b['singkatan'] ?? 'pcs') ?>
                </td>
                <td style="text-align: center;">
                  <?php if ($isZero): ?>
                    <span class="badge badge-danger"><i class="bi bi-x-circle-fill"></i> Habis</span>
                  <?php elseif ($isCritical): ?>
                    <span class="badge badge-warning"><i class="bi bi-exclamation-circle-fill"></i> Menipis</span>
                  <?php else: ?>
                    <span class="badge badge-success"><i class="bi bi-check-circle-fill"></i> Aman</span>
                  <?php endif; ?>
                </td>
                <td style="text-align: center;">
                  <div style="display: inline-flex; gap: 4px;">
                    <a href="masuk.php?id_barang=<?= $b['id'] ?>" class="btn btn-secondary btn-sm" title="Tambah Stok (In)" style="padding: 4px 8px;">
                      <i class="bi bi-plus-lg text-success"></i>
                    </a>
                    <a href="keluar.php?id_barang=<?= $b['id'] ?>" class="btn btn-secondary btn-sm" title="Keluarkan Barang (Out)" style="padding: 4px 8px;">
                      <i class="bi bi-dash-lg text-danger"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="9" style="text-align: center; padding: 24px; color: var(--text-muted);">
                Belum ada data barang. Silakan daftarkan di menu <a href="barang.php" class="text-primary">Data Barang</a>.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent Transactions Feed Desktop (2 Kolom) -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px;">
    <!-- Masuk Terakhir -->
    <div class="glass-card">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h3 style="font-size: 0.95rem; font-weight: 800; color: var(--success); display: flex; align-items: center; gap: 6px;">
          <i class="bi bi-arrow-down-left-circle-fill"></i> Pemasukan Terakhir (Dari Supplier)
        </h3>
        <a href="laporan.php?tipe=masuk" style="font-size: 0.75rem; color: var(--primary); text-decoration: none;">Lihat Semua</a>
      </div>

      <?php if (!empty($recentIn)): ?>
        <div style="display: flex; flex-direction: column; gap: 8px;">
          <?php foreach ($recentIn as $in): ?>
            <div style="background: var(--input-bg); border: 1px solid var(--card-border); border-radius: 10px; padding: 10px 12px; display: flex; justify-content: space-between; align-items: center;">
              <div>
                <div style="font-weight: 700; font-size: 0.82rem; color: var(--text-main);"><?= htmlspecialchars($in['nama_supplier']) ?></div>
                <div style="font-size: 0.7rem; color: var(--text-muted);"><?= htmlspecialchars($in['no_masuk']) ?> • <?= date('d M Y', strtotime($in['tanggal_masuk'])) ?></div>
              </div>
              <div style="text-align: right;">
                <span class="badge badge-success">+<?= formatStok($in['total_qty']) ?> item</span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p style="font-size: 0.8rem; color: var(--text-dim); text-align: center; padding: 12px;">Belum ada riwayat pemasukan.</p>
      <?php endif; ?>
    </div>

    <!-- Keluar Terakhir -->
    <div class="glass-card">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h3 style="font-size: 0.95rem; font-weight: 800; color: var(--danger); display: flex; align-items: center; gap: 6px;">
          <i class="bi bi-arrow-up-right-circle-fill"></i> Pengeluaran Terakhir (Ke PIC)
        </h3>
        <a href="laporan.php?tipe=keluar" style="font-size: 0.75rem; color: var(--primary); text-decoration: none;">Lihat Semua</a>
      </div>

      <?php if (!empty($recentOut)): ?>
        <div style="display: flex; flex-direction: column; gap: 8px;">
          <?php foreach ($recentOut as $out): ?>
            <div style="background: var(--input-bg); border: 1px solid var(--card-border); border-radius: 10px; padding: 10px 12px; display: flex; justify-content: space-between; align-items: center;">
              <div>
                <div style="font-weight: 700; font-size: 0.82rem; color: var(--text-main);"><?= htmlspecialchars($out['nama_pic']) ?> (<?= htmlspecialchars($out['departemen']) ?>)</div>
                <div style="font-size: 0.7rem; color: var(--text-muted);"><?= htmlspecialchars($out['keperluan']) ?> • <?= date('d M Y', strtotime($out['tanggal_keluar'])) ?></div>
              </div>
              <div style="text-align: right;">
                <span class="badge badge-danger">-<?= formatStok($out['total_qty']) ?> item</span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p style="font-size: 0.8rem; color: var(--text-dim); text-align: center; padding: 12px;">Belum ada riwayat pengeluaran.</p>
      <?php endif; ?>
    </div>
  </div>

</div>

<script>
// Auto Carousel Banner Slider di HP
document.addEventListener('DOMContentLoaded', () => {
  const mTrack = document.getElementById('mTrack');
  const mDots = document.querySelectorAll('#mDots .c-dot');
  if (mTrack && mDots.length > 0) {
    let currentSlide = 0;
    setInterval(() => {
      currentSlide = (currentSlide + 1) % 4;
      mTrack.style.transform = `translateX(-${currentSlide * 25}%)`;
      mDots.forEach((d, i) => d.classList.toggle('active', i === currentSlide));
    }, 4000);
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
