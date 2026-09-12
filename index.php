<?php
// index.php - Dashboard Monitoring Aktual Stok Realtime
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

} catch (Exception $e) {
    $errorMsg = $e->getMessage();
}
?>

<!-- Quick Action Banners -->
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

<!-- Stats Counters -->
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

<!-- Tabel Monitoring Aktual Stok Realtime -->
<div class="glass-card">
  <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px;">
    <div>
      <h2 style="font-size: 1.15rem; font-weight: 800; color: #ffffff; display: flex; align-items: center; gap: 8px;">
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
                <strong style="color: #60a5fa;"><?= htmlspecialchars($b['kode_barang']) ?></strong>
                <?php if ($b['part_number']): ?>
                  <div style="font-size: 0.75rem; color: #93c5fd; font-weight: 700;"><i class="bi bi-tag-fill"></i> <?= htmlspecialchars($b['part_number']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <div style="font-weight: 700; color: #ffffff;"><?= htmlspecialchars($b['nama_barang']) ?></div>
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
                <span style="font-size: 0.8rem; color: #cbd5e1;"><i class="bi bi-archive-fill text-dim"></i> <?= htmlspecialchars($b['lokasi_rak'] ?? '-') ?></span>
              </td>
              <td style="text-align: right; font-weight: 800; font-size: 0.95rem; <?= $isCritical ? 'color: #f87171;' : 'color: #34d399;' ?>">
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
              Belum ada data barang. Silakan daftarkan di menu <a href="barang.php" style="color: #60a5fa;">Data Barang</a>.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Recent Transactions Feed -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px;">
  <!-- Masuk Terakhir -->
  <div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
      <h3 style="font-size: 0.95rem; font-weight: 700; color: #34d399; display: flex; align-items: center; gap: 6px;">
        <i class="bi bi-arrow-down-left-circle-fill"></i> Pemasukan Terakhir (Dari Supplier)
      </h3>
      <a href="laporan.php?tipe=masuk" style="font-size: 0.75rem; color: #60a5fa; text-decoration: none;">Lihat Semua</a>
    </div>

    <?php if (!empty($recentIn)): ?>
      <div style="display: flex; flex-direction: column; gap: 8px;">
        <?php foreach ($recentIn as $in): ?>
          <div style="background: rgba(15, 23, 42, 0.4); border: 1px solid var(--card-border); border-radius: 8px; padding: 10px 12px; display: flex; justify-content: space-between; align-items: center;">
            <div>
              <div style="font-weight: 700; font-size: 0.82rem; color: #ffffff;"><?= htmlspecialchars($in['nama_supplier']) ?></div>
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
      <h3 style="font-size: 0.95rem; font-weight: 700; color: #f87171; display: flex; align-items: center; gap: 6px;">
        <i class="bi bi-arrow-up-right-circle-fill"></i> Pengeluaran Terakhir (Ke PIC)
      </h3>
      <a href="laporan.php?tipe=keluar" style="font-size: 0.75rem; color: #60a5fa; text-decoration: none;">Lihat Semua</a>
    </div>

    <?php if (!empty($recentOut)): ?>
      <div style="display: flex; flex-direction: column; gap: 8px;">
        <?php foreach ($recentOut as $out): ?>
          <div style="background: rgba(15, 23, 42, 0.4); border: 1px solid var(--card-border); border-radius: 8px; padding: 10px 12px; display: flex; justify-content: space-between; align-items: center;">
            <div>
              <div style="font-weight: 700; font-size: 0.82rem; color: #ffffff;"><?= htmlspecialchars($out['nama_pic']) ?> (<?= htmlspecialchars($out['departemen']) ?>)</div>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
