<?php
// export.php - Generator Ekspor Excel & PDF Cetak
require_once __DIR__ . '/config/database.php';

$type = $_GET['type'] ?? 'stok_excel';
$namaKantor = getSetting('nama_kantor', 'PT MaoneArt Teknologi Presisi');
$namaGudang = getSetting('nama_gudang', 'Gudang Pusat & Workshop Logistik');

// 0. Backup Database SQL
if ($type === 'backup_db') {
    $sqlFile = __DIR__ . '/db_inventory.sql';
    if (!file_exists($sqlFile)) {
        $sqlFile = __DIR__ . '/db_inventaris.sql';
    }
    if (file_exists($sqlFile)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="Backup_db_inventory_' . date('Ymd_His') . '.sql"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($sqlFile));
        readfile($sqlFile);
        exit;
    }
}

// 1. Ekspor Stok Aktual ke Excel
if ($type === 'stok_excel') {
    $filename = "Aktual_Stok_Gudang_" . date('Ymd_His') . ".xls";
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    $items = $pdo->query("
        SELECT b.*, k.nama_kategori, s.nama_satuan, s.singkatan
        FROM barang b
        LEFT JOIN kategori k ON b.id_kategori = k.id
        LEFT JOIN satuan s ON b.id_satuan = s.id
        ORDER BY b.nama_barang ASC
    ")->fetchAll();
    ?>
    <table border="1">
      <thead>
        <tr style="background-color: #2563eb; color: #ffffff; font-weight: bold;">
          <th colspan="7" style="font-size: 16px; text-align: center; height: 35px;">
            <?= htmlspecialchars($namaKantor) ?> - LAPORAN AKTUAL STOK GUDANG
          </th>
        </tr>
        <tr>
          <th colspan="7" style="text-align: center; font-size: 12px;">
            Dicetak pada: <?= date('d F Y H:i:s') ?> | Lokasi: <?= htmlspecialchars($namaGudang) ?>
          </th>
        </tr>
        <tr style="background-color: #e2e8f0; font-weight: bold;">
          <th>No</th>
          <th>Kode Barang</th>
          <th>Barcode</th>
          <th>Nama Barang</th>
          <th>Kategori</th>
          <th>Stok Aktual</th>
          <th>Satuan</th>
          <th>Stok Min</th>
          <th>Lokasi Rak</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1; foreach ($items as $it): 
          $status = ($it['stok_saat_ini'] <= $it['stok_minimum']) ? 'MENIPIS / KRITIS' : 'AMAN';
        ?>
          <tr>
            <td style="text-align: center;"><?= $no++ ?></td>
            <td style="mso-number-format:'\@';"><?= htmlspecialchars($it['kode_barang']) ?></td>
            <td style="mso-number-format:'\@';"><?= htmlspecialchars($it['barcode'] ?: '-') ?></td>
            <td><?= htmlspecialchars($it['nama_barang']) ?></td>
            <td><?= htmlspecialchars($it['nama_kategori'] ?: 'Umum') ?></td>
            <td style="text-align: right;"><?= $it['stok_saat_ini'] ?></td>
            <td><?= htmlspecialchars($it['singkatan']) ?></td>
            <td style="text-align: right;"><?= $it['stok_minimum'] ?></td>
            <td><?= htmlspecialchars($it['lokasi_rak'] ?: '-') ?></td>
            <td style="text-align: center; font-weight: bold; <?= $status !== 'AMAN' ? 'color: red;' : 'color: green;' ?>">
              <?= $status ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php
    exit;
}

// 2. Ekspor Laporan Periode ke Excel
if ($type === 'laporan_excel') {
    $tglMulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
    $tglSelesai = $_GET['tgl_selesai'] ?? date('Y-m-d');
    $tipeFilter = $_GET['tipe'] ?? 'semua';

    $filename = "Laporan_Mutasi_Gudang_{$tglMulai}_sd_{$tglSelesai}.xls";
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    $listMasuk = $pdo->prepare("
        SELECT tm.*, s.nama_supplier 
        FROM transaksi_masuk tm JOIN supplier s ON tm.id_supplier = s.id 
        WHERE tm.tanggal_masuk BETWEEN ? AND ? ORDER BY tm.tanggal_masuk ASC
    ");
    $listMasuk->execute([$tglMulai, $tglSelesai]);
    $masukRows = $listMasuk->fetchAll();

    $listKeluar = $pdo->prepare("
        SELECT tk.*, p.nama_pic, p.departemen 
        FROM transaksi_keluar tk JOIN pic p ON tk.id_pic = p.id 
        WHERE tk.tanggal_keluar BETWEEN ? AND ? ORDER BY tk.tanggal_keluar ASC
    ");
    $listKeluar->execute([$tglMulai, $tglSelesai]);
    $keluarRows = $listKeluar->fetchAll();
    ?>
    <table border="1">
      <thead>
        <tr style="background-color: #0f172a; color: #ffffff; font-weight: bold;">
          <th colspan="7" style="font-size: 15px; text-align: center; height: 35px;">
            <?= htmlspecialchars($namaKantor) ?> - REKAPITULASI ARUS BARANG
          </th>
        </tr>
        <tr>
          <th colspan="7" style="text-align: center;">
            Periode: <?= $tglMulai ?> s/d <?= $tglSelesai ?>
          </th>
        </tr>
      </thead>
    </table>

    <br>
    <h3>1. DAFTAR PEMASUKAN BARANG (STOCK IN)</h3>
    <table border="1">
      <tr style="background-color: #d1fae5; font-weight: bold;">
        <th>No</th><th>No. Masuk</th><th>Tanggal</th><th>Supplier</th><th>No. Surat Jalan</th><th>Total Item</th><th>Total Qty</th>
      </tr>
      <?php $no = 1; foreach ($masukRows as $m): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td style="mso-number-format:'\@';"><?= $m['no_masuk'] ?></td>
          <td><?= $m['tanggal_masuk'] ?></td>
          <td><?= htmlspecialchars($m['nama_supplier']) ?></td>
          <td><?= htmlspecialchars($m['no_surat_jalan_po']) ?></td>
          <td style="text-align: right;"><?= $m['total_item'] ?></td>
          <td style="text-align: right; font-weight: bold;"><?= $m['total_qty'] ?></td>
        </tr>
      <?php endforeach; ?>
    </table>

    <br>
    <h3>2. DAFTAR PENGELUARAN BARANG (STOCK OUT)</h3>
    <table border="1">
      <tr style="background-color: #fee2e2; font-weight: bold;">
        <th>No</th><th>No. Keluar</th><th>Tanggal</th><th>PIC Pengambil</th><th>Departemen</th><th>Keperluan</th><th>Total Item</th><th>Total Qty</th>
      </tr>
      <?php $no = 1; foreach ($keluarRows as $k): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td style="mso-number-format:'\@';"><?= $k['no_keluar'] ?></td>
          <td><?= $k['tanggal_keluar'] ?></td>
          <td><?= htmlspecialchars($k['nama_pic']) ?></td>
          <td><?= htmlspecialchars($k['departemen']) ?></td>
          <td><?= htmlspecialchars($k['keperluan']) ?></td>
          <td style="text-align: right;"><?= $k['total_item'] ?></td>
          <td style="text-align: right; font-weight: bold;"><?= $k['total_qty'] ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
    <?php
    exit;
}

// 3. Tampilan Print PDF-Ready (Stok & Laporan)
$isStokPdf = ($type === 'stok_pdf');
$items = $pdo->query("
    SELECT b.*, k.nama_kategori, s.singkatan
    FROM barang b
    LEFT JOIN kategori k ON b.id_kategori = k.id
    LEFT JOIN satuan s ON b.id_satuan = s.id
    ORDER BY b.nama_barang ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan Gudang - <?= htmlspecialchars($namaKantor) ?></title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 11pt; color: #111; margin: 20px; }
    .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 16px; }
    .header h2 { margin: 0 0 4px 0; font-size: 16pt; }
    .header p { margin: 0; font-size: 9pt; color: #555; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 9.5pt; }
    th, td { border: 1px solid #333; padding: 6px 8px; text-align: left; }
    th { background: #f0f0f0; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .badge-kritis { font-weight: bold; color: #b91c1c; }
    .signature-row { margin-top: 50px; display: flex; justify-content: space-between; page-break-inside: avoid; }
    .sig-box { width: 200px; text-align: center; }
    .sig-space { height: 60px; }
    @media print {
      .no-print { display: none; }
      body { margin: 0; }
    }
  </style>
</head>
<body>

<div class="no-print" style="margin-bottom: 20px; background: #e0f2fe; padding: 10px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
  <span>🖨️ Dokumen siap dicetak atau disimpan sebagai PDF (Ctrl+P atau Command+P).</span>
  <button onclick="window.print()" style="padding: 6px 14px; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
    Cetak / Simpan PDF
  </button>
</div>

<div class="header">
  <h2><?= htmlspecialchars($namaKantor) ?></h2>
  <p><?= htmlspecialchars(getSetting('alamat_kantor', 'Kawasan Industri')) ?> | Telp: <?= htmlspecialchars(getSetting('telepon_kantor', '-')) ?></p>
  <p><strong>DOKUMEN INVENTORY: AKTUAL STOK LOGISTIK GUDANG PABRIK</strong></p>
  <p style="font-size: 8.5pt;">Tanggal Cetak: <?= date('d/m/Y H:i') ?> | Oleh: <?= htmlspecialchars(getUserName()) ?></p>
</div>

<table>
  <thead>
    <tr>
      <th style="width: 30px;" class="text-center">No</th>
      <th>Kode Barang</th>
      <th>Nama Barang & Spesifikasi</th>
      <th>Kategori</th>
      <th class="text-right">Stok Aktual</th>
      <th class="text-right">Stok Min</th>
      <th>Lokasi Rak</th>
      <th class="text-center">Status</th>
    </tr>
  </thead>
  <tbody>
    <?php $no = 1; foreach ($items as $b): 
      $isCrit = $b['stok_saat_ini'] <= $b['stok_minimum'];
    ?>
      <tr>
        <td class="text-center"><?= $no++ ?></td>
        <td><strong><?= htmlspecialchars($b['kode_barang']) ?></strong></td>
        <td>
          <strong><?= htmlspecialchars($b['nama_barang']) ?></strong>
          <?php if ($b['spesifikasi']): ?><div style="font-size: 8pt; color: #666;"><?= htmlspecialchars($b['spesifikasi']) ?></div><?php endif; ?>
        </td>
        <td><?= htmlspecialchars($b['nama_kategori'] ?: 'Umum') ?></td>
        <td class="text-right" style="font-weight: bold;"><?= formatStok($b['stok_saat_ini']) ?> <?= htmlspecialchars($b['singkatan']) ?></td>
        <td class="text-right"><?= formatStok($b['stok_minimum']) ?> <?= htmlspecialchars($b['singkatan']) ?></td>
        <td><?= htmlspecialchars($b['lokasi_rak'] ?: '-') ?></td>
        <td class="text-center <?= $isCrit ? 'badge-kritis' : '' ?>">
          <?= $isCrit ? 'PERLU RESTOCK' : 'AMAN' ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<div class="signature-row">
  <div class="sig-box">
    <p>Petugas Gudang,</p>
    <div class="sig-space"></div>
    <p><strong>( ................................ )</strong></p>
  </div>
  <div class="sig-box">
    <p>Mengetahui / Supervisor,</p>
    <div class="sig-space"></div>
    <p><strong>( ................................ )</strong></p>
  </div>
</div>

</body>
</html>
