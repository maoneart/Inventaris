<?php
// laporan.php - Laporan Rekapitulasi Arus Barang
$pageTitle = "Laporan & Rekapitulasi";
require_once __DIR__ . '/config/database.php';

$tglMulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tglSelesai = $_GET['tgl_selesai'] ?? date('Y-m-d');
$tipe = $_GET['tipe'] ?? 'semua'; // 'semua', 'masuk', 'keluar'

// Query Transaksi Masuk
$listMasuk = [];
if ($tipe === 'semua' || $tipe === 'masuk') {
    $stmtM = $pdo->prepare("
        SELECT tm.*, s.nama_supplier, s.kode_supplier
        FROM transaksi_masuk tm
        JOIN supplier s ON tm.id_supplier = s.id
        WHERE tm.tanggal_masuk BETWEEN ? AND ?
        ORDER BY tm.tanggal_masuk DESC, tm.id DESC
    ");
    $stmtM->execute([$tglMulai, $tglSelesai]);
    $listMasuk = $stmtM->fetchAll();
}

// Query Transaksi Keluar
$listKeluar = [];
if ($tipe === 'semua' || $tipe === 'keluar') {
    $stmtK = $pdo->prepare("
        SELECT tk.*, p.nama_pic, p.departemen, p.nip_nik
        FROM transaksi_keluar tk
        JOIN pic p ON tk.id_pic = p.id
        WHERE tk.tanggal_keluar BETWEEN ? AND ?
        ORDER BY tk.tanggal_keluar DESC, tk.id DESC
    ");
    $stmtK->execute([$tglMulai, $tglSelesai]);
    $listKeluar = $stmtK->fetchAll();
}

// Hitung Ringkasan
$totalQtyMasuk = array_sum(array_column($listMasuk, 'total_qty'));
$totalQtyKeluar = array_sum(array_column($listKeluar, 'total_qty'));

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <h2 style="font-size: 1.25rem; font-weight: 800; color: #ffffff; display: flex; align-items: center; gap: 8px;">
      <i class="bi bi-file-earmark-text-fill text-primary"></i> Laporan Arus Barang & Mutasi Stok
    </h2>
    <p style="font-size: 0.78rem; color: var(--text-muted);">Rekapitulasi penerimaan barang dari supplier & pengeluaran ke PIC teknisi</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <a href="export.php?type=laporan_excel&tgl_mulai=<?= $tglMulai ?>&tgl_selesai=<?= $tglSelesai ?>&tipe=<?= $tipe ?>" class="btn btn-secondary btn-sm">
      <i class="bi bi-file-earmark-excel-fill text-success"></i> Ekspor Excel
    </a>
    <a href="export.php?type=laporan_pdf&tgl_mulai=<?= $tglMulai ?>&tgl_selesai=<?= $tglSelesai ?>&tipe=<?= $tipe ?>" target="_blank" class="btn btn-secondary btn-sm">
      <i class="bi bi-printer-fill text-danger"></i> Cetak / PDF
    </a>
  </div>
</div>

<!-- Filter Bar Card -->
<div class="glass-card" style="padding: 16px 20px;">
  <form action="laporan.php" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; align-items: end;">
    <div class="form-group" style="margin-bottom: 0;">
      <label class="form-label">Dari Tanggal</label>
      <input type="date" name="tgl_mulai" class="form-control" value="<?= htmlspecialchars($tglMulai) ?>">
    </div>
    <div class="form-group" style="margin-bottom: 0;">
      <label class="form-label">Sampai Tanggal</label>
      <input type="date" name="tgl_selesai" class="form-control" value="<?= htmlspecialchars($tglSelesai) ?>">
    </div>
    <div class="form-group" style="margin-bottom: 0;">
      <label class="form-label">Jenis Laporan</label>
      <select name="tipe" class="form-select">
        <option value="semua" <?= $tipe === 'semua' ? 'selected' : '' ?>>Semua (Masuk & Keluar)</option>
        <option value="masuk" <?= $tipe === 'masuk' ? 'selected' : '' ?>>Barang Masuk Saja (Supplier)</option>
        <option value="keluar" <?= $tipe === 'keluar' ? 'selected' : '' ?>>Barang Keluar Saja (PIC)</option>
      </select>
    </div>
    <div>
      <button type="submit" class="btn btn-primary w-full" style="width: 100%;">
        <i class="bi bi-funnel-fill"></i> Terapkan Filter
      </button>
    </div>
  </form>
</div>

<!-- Ringkasan Periode Grid -->
<div class="stats-grid">
  <div class="stat-box">
    <div class="stat-icon green">
      <i class="bi bi-arrow-down-left-circle-fill"></i>
    </div>
    <div class="stat-info">
      <div class="stat-label">Total Masuk (Periode Ini)</div>
      <div class="stat-value" style="color: #34d399;">+<?= formatStok($totalQtyMasuk) ?></div>
    </div>
  </div>

  <div class="stat-box">
    <div class="stat-icon red">
      <i class="bi bi-arrow-up-right-circle-fill"></i>
    </div>
    <div class="stat-info">
      <div class="stat-label">Total Keluar (Periode Ini)</div>
      <div class="stat-value" style="color: #f87171;">-<?= formatStok($totalQtyKeluar) ?></div>
    </div>
  </div>

  <div class="stat-box">
    <div class="stat-icon blue">
      <i class="bi bi-arrow-left-right"></i>
    </div>
    <div class="stat-info">
      <div class="stat-label">Selisih Arus Barang</div>
      <div class="stat-value"><?= formatStok($totalQtyMasuk - $totalQtyKeluar) ?></div>
    </div>
  </div>
</div>

<?php if ($tipe === 'semua' || $tipe === 'masuk'): ?>
<!-- Tabel Pemasukan Barang -->
<div class="glass-card">
  <h3 style="font-size: 1.05rem; font-weight: 700; color: #34d399; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
    <i class="bi bi-box-arrow-in-down"></i> Rincian Pemasukan Barang (<?= count($listMasuk) ?> Pengiriman)
  </h3>
  <div class="table-responsive">
    <table class="modern-table">
      <thead>
        <tr>
          <th>No. Transaksi</th>
          <th>Tanggal</th>
          <th>Supplier Pengirim</th>
          <th>No. Surat Jalan / PO</th>
          <th style="text-align: right;">Total Item</th>
          <th style="text-align: right;">Total Qty</th>
          <th>Catatan</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($listMasuk)): ?>
          <?php foreach ($listMasuk as $m): ?>
            <tr>
              <td><strong style="color: #60a5fa;"><?= htmlspecialchars($m['no_masuk']) ?></strong></td>
              <td><?= date('d/m/Y', strtotime($m['tanggal_masuk'])) ?></td>
              <td><strong style="color: #ffffff;"><?= htmlspecialchars($m['nama_supplier']) ?></strong></td>
              <td><?= htmlspecialchars($m['no_surat_jalan_po'] ?: '-') ?></td>
              <td style="text-align: right;"><?= $m['total_item'] ?> jenis</td>
              <td style="text-align: right; font-weight: 700; color: #34d399;">+<?= formatStok($m['total_qty']) ?></td>
              <td><span style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($m['catatan'] ?: '-') ?></span></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="7" style="text-align: center; color: var(--text-dim); padding: 16px;">Tidak ada data pemasukan pada rentang tanggal ini.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($tipe === 'semua' || $tipe === 'keluar'): ?>
<!-- Tabel Pengeluaran Barang -->
<div class="glass-card">
  <h3 style="font-size: 1.05rem; font-weight: 700; color: #f87171; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
    <i class="bi bi-box-arrow-up-right"></i> Rincian Pengeluaran Barang (<?= count($listKeluar) ?> Pengambilan)
  </h3>
  <div class="table-responsive">
    <table class="modern-table">
      <thead>
        <tr>
          <th>No. Transaksi</th>
          <th>Tanggal</th>
          <th>PIC Pengambil</th>
          <th>Departemen</th>
          <th>Keperluan / Proyek</th>
          <th style="text-align: right;">Total Item</th>
          <th style="text-align: right;">Total Qty</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($listKeluar)): ?>
          <?php foreach ($listKeluar as $k): ?>
            <tr>
              <td><strong style="color: #60a5fa;"><?= htmlspecialchars($k['no_keluar']) ?></strong></td>
              <td><?= date('d/m/Y', strtotime($k['tanggal_keluar'])) ?></td>
              <td><strong style="color: #ffffff;"><?= htmlspecialchars($k['nama_pic']) ?></strong></td>
              <td><span class="badge badge-purple"><?= htmlspecialchars($k['departemen']) ?></span></td>
              <td><?= htmlspecialchars($k['keperluan']) ?></td>
              <td style="text-align: right;"><?= $k['total_item'] ?> jenis</td>
              <td style="text-align: right; font-weight: 700; color: #f87171;">-<?= formatStok($k['total_qty']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="7" style="text-align: center; color: var(--text-dim); padding: 16px;">Tidak ada data pengeluaran pada rentang tanggal ini.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
