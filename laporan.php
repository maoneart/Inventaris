<?php
// laporan.php - Laporan Rekapitulasi Arus Barang & Manajemen Koreksi Transaksi
$pageTitle = "Laporan & Rekapitulasi";
require_once __DIR__ . '/config/database.php';

$tglMulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tglSelesai = $_GET['tgl_selesai'] ?? date('Y-m-d');
$tipe = $_GET['tipe'] ?? 'semua'; // 'semua', 'masuk', 'keluar'

$redirectUrl = "laporan.php?tgl_mulai=" . urlencode($tglMulai) . 
               "&tgl_selesai=" . urlencode($tglSelesai) . 
               "&tipe=" . urlencode($tipe);

// ==========================================================================
// 1. AJAX ENDPOINT: AMBIL DETAIL TRANSAKSI (VIEW & EDIT)
// ==========================================================================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_detail') {
    header('Content-Type: application/json; charset=utf-8');
    $type = $_GET['type'] ?? '';
    $id = (int)($_GET['id'] ?? 0);

    if ($type === 'masuk') {
        $stmt = $pdo->prepare("
            SELECT tm.*, s.nama_supplier, s.kode_supplier, s.kontak_person, s.no_telp
            FROM transaksi_masuk tm
            JOIN supplier s ON tm.id_supplier = s.id
            WHERE tm.id = ?
        ");
        $stmt->execute([$id]);
        $header = $stmt->fetch();
        if (!$header) {
            echo json_encode(['status' => 'error', 'message' => 'Transaksi masuk tidak ditemukan.']);
            exit;
        }

        $stmtItems = $pdo->prepare("
            SELECT d.*, b.nama_barang, b.kode_barang, b.part_number, b.stok_saat_ini, b.stok_minimum, s.singkatan, s.nama_satuan
            FROM detail_transaksi_masuk d
            JOIN barang b ON d.id_barang = b.id
            LEFT JOIN satuan s ON d.id_satuan = s.id
            WHERE d.id_transaksi_masuk = ?
            ORDER BY d.id ASC
        ");
        $stmtItems->execute([$id]);
        $items = $stmtItems->fetchAll();

        echo json_encode([
            'status' => 'success',
            'type' => 'masuk',
            'header' => [
                'id' => (int)$header['id'],
                'no_transaksi' => $header['no_masuk'],
                'tanggal' => $header['tanggal_masuk'],
                'target_nama' => $header['nama_supplier'],
                'target_label' => 'Supplier Pengirim',
                'target_sub' => 'Kode: ' . $header['kode_supplier'] . ($header['kontak_person'] ? ' | PIC: ' . $header['kontak_person'] : ''),
                'dokumen_ref' => $header['no_surat_jalan_po'],
                'dokumen_label' => 'No. Surat Jalan / PO',
                'catatan' => $header['catatan'],
                'total_item' => (int)$header['total_item'],
                'total_qty' => (float)$header['total_qty'],
                'created_at' => $header['created_at']
            ],
            'items' => array_map(function($it) {
                return [
                    'id' => (int)$it['id'],
                    'id_barang' => (int)$it['id_barang'],
                    'nama_barang' => $it['nama_barang'],
                    'kode_barang' => $it['kode_barang'],
                    'part_number' => $it['part_number'],
                    'stok_saat_ini' => (float)$it['stok_saat_ini'],
                    'stok_minimum' => (float)$it['stok_minimum'],
                    'qty' => (float)$it['qty'],
                    'satuan' => $it['singkatan'] ?: $it['nama_satuan'],
                    'keterangan' => $it['keterangan']
                ];
            }, $items)
        ]);
        exit;
    } elseif ($type === 'keluar') {
        $stmt = $pdo->prepare("
            SELECT tk.*, p.nama_pic, p.departemen, p.nip_nik, p.no_hp
            FROM transaksi_keluar tk
            JOIN pic p ON tk.id_pic = p.id
            WHERE tk.id = ?
        ");
        $stmt->execute([$id]);
        $header = $stmt->fetch();
        if (!$header) {
            echo json_encode(['status' => 'error', 'message' => 'Transaksi keluar tidak ditemukan.']);
            exit;
        }

        $stmtItems = $pdo->prepare("
            SELECT d.*, b.nama_barang, b.kode_barang, b.part_number, b.stok_saat_ini, b.stok_minimum, s.singkatan, s.nama_satuan
            FROM detail_transaksi_keluar d
            JOIN barang b ON d.id_barang = b.id
            LEFT JOIN satuan s ON d.id_satuan = s.id
            WHERE d.id_transaksi_keluar = ?
            ORDER BY d.id ASC
        ");
        $stmtItems->execute([$id]);
        $items = $stmtItems->fetchAll();

        echo json_encode([
            'status' => 'success',
            'type' => 'keluar',
            'header' => [
                'id' => (int)$header['id'],
                'no_transaksi' => $header['no_keluar'],
                'tanggal' => $header['tanggal_keluar'],
                'target_nama' => $header['nama_pic'],
                'target_label' => 'PIC / Teknisi Pengambil',
                'target_sub' => 'Dept: ' . $header['departemen'] . ($header['nip_nik'] ? ' | NIK: ' . $header['nip_nik'] : ''),
                'dokumen_ref' => $header['keperluan'],
                'dokumen_label' => 'Keperluan / Lokasi Kerja',
                'catatan' => $header['catatan'],
                'jenis_pengeluaran' => $header['jenis_pengeluaran'],
                'total_item' => (int)$header['total_item'],
                'total_qty' => (float)$header['total_qty'],
                'created_at' => $header['created_at']
            ],
            'items' => array_map(function($it) {
                return [
                    'id' => (int)$it['id'],
                    'id_barang' => (int)$it['id_barang'],
                    'nama_barang' => $it['nama_barang'],
                    'kode_barang' => $it['kode_barang'],
                    'part_number' => $it['part_number'],
                    'stok_saat_ini' => (float)$it['stok_saat_ini'],
                    'stok_minimum' => (float)$it['stok_minimum'],
                    'qty' => (float)$it['qty'],
                    'satuan' => $it['singkatan'] ?: $it['nama_satuan'],
                    'keterangan' => $it['keterangan']
                ];
            }, $items)
        ]);
        exit;
    }
    echo json_encode(['status' => 'error', 'message' => 'Tipe transaksi tidak valid.']);
    exit;
}

// ==========================================================================
// 2. SIMPAN EDIT / KOREKSI TRANSAKSI (QTY & DATA DOKUMEN)
// ==========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'simpan_edit_transaksi') {
    $type = $_POST['trans_type'] ?? '';
    $transId = (int)($_POST['trans_id'] ?? 0);
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $no_ref = trim($_POST['no_ref'] ?? '');
    $catatan = trim($_POST['catatan'] ?? '');

    $itemIds = $_POST['item_id'] ?? [];
    $itemQtys = $_POST['item_qty'] ?? [];
    $itemKets = $_POST['item_keterangan'] ?? [];

    if ($transId <= 0 || empty($itemIds)) {
        setFlash('danger', 'Gagal Simpan', 'Data transaksi atau item tidak valid.');
        header('Location: ' . $redirectUrl);
        exit;
    }

    try {
        $pdo->beginTransaction();

        if ($type === 'masuk') {
            $totalItem = 0;
            $totalQty = 0;

            $stmtOld = $pdo->prepare("SELECT id_barang, qty FROM detail_transaksi_masuk WHERE id = ? AND id_transaksi_masuk = ?");
            $stmtBrg = $pdo->prepare("SELECT nama_barang, stok_saat_ini FROM barang WHERE id = ? FOR UPDATE");
            $stmtUpdStok = $pdo->prepare("UPDATE barang SET stok_saat_ini = stok_saat_ini + ? WHERE id = ?");
            $stmtUpdDetail = $pdo->prepare("UPDATE detail_transaksi_masuk SET qty = ?, keterangan = ? WHERE id = ?");

            for ($i = 0; $i < count($itemIds); $i++) {
                $dId = (int)$itemIds[$i];
                $newQty = (float)$itemQtys[$i];
                $ket = trim($itemKets[$i] ?? '');

                if ($newQty <= 0) {
                    throw new Exception("Jumlah qty harus lebih dari 0.");
                }

                $stmtOld->execute([$dId, $transId]);
                $oldRow = $stmtOld->fetch();
                if (!$oldRow) continue;

                $oldQty = (float)$oldRow['qty'];
                $bId = (int)$oldRow['id_barang'];
                $selisih = $newQty - $oldQty; // Jika new 8, old 10 => selisih = -2 (stok gudang dikurangi 2)

                if ($selisih != 0) {
                    $stmtBrg->execute([$bId]);
                    $bRow = $stmtBrg->fetch();
                    if (!$bRow) throw new Exception("Barang ID $bId tidak ditemukan.");

                    if ($selisih < 0 && ($bRow['stok_saat_ini'] + $selisih) < 0) {
                        throw new Exception("Koreksi qty untuk <strong>{$bRow['nama_barang']}</strong> tidak dapat dilakukan karena sisa stok fisik di gudang saat ini hanya {$bRow['stok_saat_ini']}, tidak cukup untuk pengurangan sebesar " . abs($selisih) . ".");
                    }

                    $stmtUpdStok->execute([$selisih, $bId]);
                }

                $stmtUpdDetail->execute([$newQty, $ket, $dId]);
                $totalItem++;
                $totalQty += $newQty;
            }

            $stmtUpdHeader = $pdo->prepare("
                UPDATE transaksi_masuk 
                SET tanggal_masuk = ?, no_surat_jalan_po = ?, catatan = ?, total_item = ?, total_qty = ? 
                WHERE id = ?
            ");
            $stmtUpdHeader->execute([$tanggal, $no_ref, $catatan, $totalItem, $totalQty, $transId]);

            $pdo->commit();
            setFlash('success', 'Koreksi Berhasil!', "Data transaksi masuk berhasil diperbarui dan stok gudang telah disesuaikan secara akurat.");
        } elseif ($type === 'keluar') {
            $totalItem = 0;
            $totalQty = 0;

            $stmtOld = $pdo->prepare("SELECT id_barang, qty FROM detail_transaksi_keluar WHERE id = ? AND id_transaksi_keluar = ?");
            $stmtBrg = $pdo->prepare("SELECT nama_barang, stok_saat_ini FROM barang WHERE id = ? FOR UPDATE");
            $stmtUpdStok = $pdo->prepare("UPDATE barang SET stok_saat_ini = stok_saat_ini - ? WHERE id = ?");
            $stmtUpdDetail = $pdo->prepare("UPDATE detail_transaksi_keluar SET qty = ?, keterangan = ? WHERE id = ?");

            for ($i = 0; $i < count($itemIds); $i++) {
                $dId = (int)$itemIds[$i];
                $newQty = (float)$itemQtys[$i];
                $ket = trim($itemKets[$i] ?? '');

                if ($newQty <= 0) {
                    throw new Exception("Jumlah qty pengeluaran harus lebih dari 0.");
                }

                $stmtOld->execute([$dId, $transId]);
                $oldRow = $stmtOld->fetch();
                if (!$oldRow) continue;

                $oldQty = (float)$oldRow['qty'];
                $bId = (int)$oldRow['id_barang'];
                $selisih = $newQty - $oldQty; // Jika new 7, old 5 => selisih = +2 (stok gudang harus dikurangi 2 lagi)

                if ($selisih != 0) {
                    $stmtBrg->execute([$bId]);
                    $bRow = $stmtBrg->fetch();
                    if (!$bRow) throw new Exception("Barang ID $bId tidak ditemukan.");

                    if ($selisih > 0 && $bRow['stok_saat_ini'] < $selisih) {
                        throw new Exception("Penambahan qty keluar untuk <strong>{$bRow['nama_barang']}</strong> melebihi stok gudang! Dibutuhkan tambahan $selisih, namun sisa stok fisik hanya {$bRow['stok_saat_ini']}.");
                    }

                    $stmtUpdStok->execute([$selisih, $bId]);
                }

                $stmtUpdDetail->execute([$newQty, $ket, $dId]);
                $totalItem++;
                $totalQty += $newQty;
            }

            $stmtUpdHeader = $pdo->prepare("
                UPDATE transaksi_keluar 
                SET tanggal_keluar = ?, keperluan = ?, catatan = ?, total_item = ?, total_qty = ? 
                WHERE id = ?
            ");
            $stmtUpdHeader->execute([$tanggal, $no_ref, $catatan, $totalItem, $totalQty, $transId]);

            $pdo->commit();
            setFlash('success', 'Koreksi Berhasil!', "Data transaksi keluar berhasil diperbarui dan stok gudang telah disesuaikan secara akurat.");
        }

        header('Location: ' . $redirectUrl);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('danger', 'Gagal Memperbarui Transaksi', $e->getMessage());
        header('Location: ' . $redirectUrl);
        exit;
    }
}

// ==========================================================================
// 3. HAPUS / BATALKAN TRANSAKSI (RESET SEMUA UNTUK INPUT ULANG)
// ==========================================================================
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && isset($_GET['type']) && isset($_GET['id'])) {
    $type = $_GET['type'];
    $delId = (int)$_GET['id'];

    try {
        $pdo->beginTransaction();
        if ($type === 'masuk') {
            $stmtGet = $pdo->prepare("SELECT no_masuk FROM transaksi_masuk WHERE id = ?");
            $stmtGet->execute([$delId]);
            $trans = $stmtGet->fetch();
            $noTrans = $trans['no_masuk'] ?? "ID $delId";

            $stmtItems = $pdo->prepare("SELECT id_barang, qty FROM detail_transaksi_masuk WHERE id_transaksi_masuk = ?");
            $stmtItems->execute([$delId]);
            $details = $stmtItems->fetchAll();

            $stmtRevert = $pdo->prepare("UPDATE barang SET stok_saat_ini = GREATEST(0, stok_saat_ini - ?) WHERE id = ?");
            foreach ($details as $d) {
                $stmtRevert->execute([$d['qty'], $d['id_barang']]);
            }

            $stmtDel = $pdo->prepare("DELETE FROM transaksi_masuk WHERE id = ?");
            $stmtDel->execute([$delId]);

            $pdo->commit();
            setFlash('success', 'Transaksi Dihapus', "Transaksi masuk <strong>$noTrans</strong> berhasil dibatalkan dan seluruh stok fisik telah dikembalikan ke kondisi semula.");
        } elseif ($type === 'keluar') {
            $stmtGet = $pdo->prepare("SELECT no_keluar FROM transaksi_keluar WHERE id = ?");
            $stmtGet->execute([$delId]);
            $trans = $stmtGet->fetch();
            $noTrans = $trans['no_keluar'] ?? "ID $delId";

            $stmtItems = $pdo->prepare("SELECT id_barang, qty FROM detail_transaksi_keluar WHERE id_transaksi_keluar = ?");
            $stmtItems->execute([$delId]);
            $details = $stmtItems->fetchAll();

            $stmtRevert = $pdo->prepare("UPDATE barang SET stok_saat_ini = stok_saat_ini + ? WHERE id = ?");
            foreach ($details as $d) {
                $stmtRevert->execute([$d['qty'], $d['id_barang']]);
            }

            $stmtDel = $pdo->prepare("DELETE FROM transaksi_keluar WHERE id = ?");
            $stmtDel->execute([$delId]);

            $pdo->commit();
            setFlash('success', 'Transaksi Dihapus', "Transaksi keluar <strong>$noTrans</strong> berhasil dibatalkan dan seluruh stok fisik telah dikembalikan ke gudang.");
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('danger', 'Gagal Hapus Transaksi', $e->getMessage());
    }
    header('Location: ' . $redirectUrl);
    exit;
}

// ==========================================================================
// 4. DATA QUERY UNTUK TABEL LAPORAN
// ==========================================================================
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

$totalQtyMasuk = array_sum(array_column($listMasuk, 'total_qty'));
$totalQtyKeluar = array_sum(array_column($listKeluar, 'total_qty'));

require_once __DIR__ . '/includes/header.php';
?>

<!-- iOS Minimalist Header Ala iPhone -->
<div class="ios-top-bar">
  <a href="index.php" class="ios-circle-back" title="Kembali ke Dashboard">
    <i class="bi bi-chevron-left"></i>
  </a>
  <h1 class="ios-bar-title">Laporan Mutasi</h1>
  <div class="ios-bar-action" style="gap: 6px;">
    <a href="export.php?type=laporan_excel&tgl_mulai=<?= $tglMulai ?>&tgl_selesai=<?= $tglSelesai ?>&tipe=<?= $tipe ?>" class="btn-pill-action btn-pill-green" title="Ekspor Excel">
      <i class="bi bi-file-earmark-excel-fill"></i> XLS
    </a>
    <a href="export.php?type=laporan_pdf&tgl_mulai=<?= $tglMulai ?>&tgl_selesai=<?= $tglSelesai ?>&tipe=<?= $tipe ?>" target="_blank" class="btn-pill-action btn-pill-red" title="Cetak PDF">
      <i class="bi bi-printer-fill"></i> PDF
    </a>
  </div>
</div>

<!-- Group 1: Filter Bar Inset Card -->
<div class="ios-form-card">
  <div class="ios-group-title">
    <i class="bi bi-funnel"></i> FILTER PERIODE & TIPE MUTASI
  </div>

  <form action="laporan.php" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; align-items: end;">
    <div>
      <label class="ios-label">Dari Tanggal</label>
      <input type="date" name="tgl_mulai" class="ios-input" value="<?= htmlspecialchars($tglMulai) ?>">
    </div>

    <div>
      <label class="ios-label">Sampai Tanggal</label>
      <input type="date" name="tgl_selesai" class="ios-input" value="<?= htmlspecialchars($tglSelesai) ?>">
    </div>

    <div>
      <label class="ios-label">Jenis Mutasi</label>
      <select name="tipe" class="ios-select">
        <option value="semua" <?= $tipe === 'semua' ? 'selected' : '' ?>>Semua (Masuk & Keluar)</option>
        <option value="masuk" <?= $tipe === 'masuk' ? 'selected' : '' ?>>Barang Masuk Saja (Supplier)</option>
        <option value="keluar" <?= $tipe === 'keluar' ? 'selected' : '' ?>>Barang Keluar Saja (PIC)</option>
      </select>
    </div>

    <div>
      <button type="submit" class="ios-btn-primary ios-btn-blue">
        <i class="bi bi-funnel-fill"></i> Tampilkan
      </button>
    </div>
  </form>
</div>

<!-- Ringkasan Periode Grid -->
<div class="stats-grid" style="margin-bottom: 18px;">
  <div class="stat-box" style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.25);">
    <div class="stat-icon green">
      <i class="bi bi-arrow-down-left-circle-fill"></i>
    </div>
    <div class="stat-info">
      <div class="stat-label">Total Masuk (Supplier)</div>
      <div class="stat-value" style="color: #34d399;">+<?= formatStok($totalQtyMasuk) ?></div>
    </div>
  </div>

  <div class="stat-box" style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25);">
    <div class="stat-icon red">
      <i class="bi bi-arrow-up-right-circle-fill"></i>
    </div>
    <div class="stat-info">
      <div class="stat-label">Total Keluar (Teknisi)</div>
      <div class="stat-value" style="color: #f87171;">-<?= formatStok($totalQtyKeluar) ?></div>
    </div>
  </div>

  <div class="stat-box" style="background: rgba(37, 99, 235, 0.08); border: 1px solid rgba(37, 99, 235, 0.25);">
    <div class="stat-icon blue">
      <i class="bi bi-arrow-left-right"></i>
    </div>
    <div class="stat-info">
      <div class="stat-label">Net Selisih Arus</div>
      <div class="stat-value" style="color: <?= ($totalQtyMasuk - $totalQtyKeluar) >= 0 ? '#34d399' : '#f87171' ?>;">
        <?= formatStok($totalQtyMasuk - $totalQtyKeluar) ?>
      </div>
    </div>
  </div>
</div>

<?php if ($tipe === 'semua' || $tipe === 'masuk'): ?>
<!-- Tabel Pemasukan Barang -->
<div class="ios-form-card" style="padding: 18px;">
  <div class="ios-group-title" style="color: #34d399; margin-bottom: 14px;">
    <i class="bi bi-box-arrow-in-down"></i> RINCIAN BARANG MASUK (<?= count($listMasuk) ?> TRANSAKSI)
  </div>

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
          <th style="text-align: center; min-width: 170px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($listMasuk)): ?>
          <?php foreach ($listMasuk as $m): ?>
            <tr>
              <td><strong class="text-primary"><?= htmlspecialchars($m['no_masuk']) ?></strong></td>
              <td><?= date('d/m/Y', strtotime($m['tanggal_masuk'])) ?></td>
              <td><strong style="color: var(--text-main);"><?= htmlspecialchars($m['nama_supplier']) ?></strong></td>
              <td><?= htmlspecialchars($m['no_surat_jalan_po'] ?: '-') ?></td>
              <td style="text-align: right;"><?= $m['total_item'] ?> jenis</td>
              <td style="text-align: right; font-weight: 700; color: var(--success);">+<?= formatStok($m['total_qty']) ?></td>
              <td style="text-align: center;">
                <div class="table-action-group">
                  <button type="button" class="btn-table-action btn-table-view" onclick="openDetailModal('masuk', <?= $m['id'] ?>)" title="Lihat Rincian Barang">
                    <i class="bi bi-eye-fill"></i> View
                  </button>
                  <button type="button" class="btn-table-action btn-table-edit" onclick="openEditModal('masuk', <?= $m['id'] ?>)" title="Koreksi Qty / Data">
                    <i class="bi bi-pencil-square"></i> Edit
                  </button>
                  <button type="button" class="btn-table-action btn-table-delete" onclick="confirmHapusLaporan('masuk', <?= $m['id'] ?>, '<?= htmlspecialchars($m['no_masuk']) ?>')" title="Hapus & Input Ulang">
                    <i class="bi bi-trash-fill"></i> Hapus
                  </button>
                </div>
              </td>
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
<div class="ios-form-card" style="padding: 18px;">
  <div class="ios-group-title" style="color: var(--danger); margin-bottom: 14px;">
    <i class="bi bi-box-arrow-up-right"></i> RINCIAN BARANG KELUAR (<?= count($listKeluar) ?> PENGAMBILAN)
  </div>

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
          <th style="text-align: center; min-width: 170px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($listKeluar)): ?>
          <?php foreach ($listKeluar as $k): ?>
            <tr>
              <td><strong class="text-primary"><?= htmlspecialchars($k['no_keluar']) ?></strong></td>
              <td><?= date('d/m/Y', strtotime($k['tanggal_keluar'])) ?></td>
              <td><strong style="color: var(--text-main);"><?= htmlspecialchars($k['nama_pic']) ?></strong></td>
              <td><span class="badge badge-purple"><?= htmlspecialchars($k['departemen']) ?></span></td>
              <td><span style="font-size: 0.82rem; color: var(--text-muted);"><?= htmlspecialchars($k['keperluan']) ?></span></td>
              <td style="text-align: right;"><?= $k['total_item'] ?> jenis</td>
              <td style="text-align: right; font-weight: 700; color: var(--danger);">-<?= formatStok($k['total_qty']) ?></td>
              <td style="text-align: center;">
                <div class="table-action-group">
                  <button type="button" class="btn-table-action btn-table-view" onclick="openDetailModal('keluar', <?= $k['id'] ?>)" title="Lihat Rincian Barang">
                    <i class="bi bi-eye-fill"></i> View
                  </button>
                  <button type="button" class="btn-table-action btn-table-edit" onclick="openEditModal('keluar', <?= $k['id'] ?>)" title="Koreksi Qty / Data">
                    <i class="bi bi-pencil-square"></i> Edit
                  </button>
                  <button type="button" class="btn-table-action btn-table-delete" onclick="confirmHapusLaporan('keluar', <?= $k['id'] ?>, '<?= htmlspecialchars($k['no_keluar']) ?>')" title="Hapus & Input Ulang">
                    <i class="bi bi-trash-fill"></i> Hapus
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="8" style="text-align: center; color: var(--text-dim); padding: 16px;">Tidak ada data pengeluaran pada rentang tanggal ini.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- ==========================================================================
     CONTAINER MODAL GLASSMORPHISM DETAIL & EDIT
     ========================================================================== -->
<div id="laporanModalOverlay" class="maoneart-modal-overlay">
  <div class="maoneart-modal-card modal-lg" id="laporanModalCard">
    <!-- Konten di-render dinamis via JavaScript -->
  </div>
</div>

<script>
function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

const modalOverlay = document.getElementById('laporanModalOverlay');
const modalCard = document.getElementById('laporanModalCard');

function closeModal() {
  modalOverlay.classList.remove('active');
}

modalOverlay.addEventListener('click', (e) => {
  if (e.target === modalOverlay) closeModal();
});

// 1. TAMPILAN VIEW DETAIL TRANSAKSI
function openDetailModal(type, id) {
  modalCard.innerHTML = `
    <div style="text-align: center; padding: 40px 0;">
      <i class="bi bi-arrow-repeat spin" style="font-size: 2rem; color: #3b82f6;"></i>
      <p style="margin-top: 10px; color: var(--text-muted); font-size: 0.85rem;">Memuat rincian transaksi...</p>
    </div>
  `;
  modalOverlay.classList.add('active');

  fetch(`laporan.php?ajax=get_detail&type=${type}&id=${id}`)
    .then(res => res.json())
    .then(data => {
      if (data.status !== 'success') {
        showAlertModal({ title: 'Gagal', message: data.message || 'Terjadi kesalahan sistem.', type: 'danger' });
        closeModal();
        return;
      }

      const h = data.header;
      const isMasuk = type === 'masuk';
      const badgeType = isMasuk ? 'badge-success' : 'badge-danger';
      const labelType = isMasuk ? 'BARANG MASUK (PENERIMAAN)' : 'BARANG KELUAR (PENGAMBILAN)';

      let itemsHtml = '';
      data.items.forEach((it, idx) => {
        itemsHtml += `
          <tr>
            <td style="text-align: center;">${idx + 1}</td>
            <td>
              <strong style="color: var(--text-main);">${escapeHtml(it.nama_barang)}</strong><br>
              <span style="font-size: 0.72rem; color: var(--text-muted);">P/N: <strong>${escapeHtml(it.part_number || '-')}</strong> | Kode: ${escapeHtml(it.kode_barang)}</span>
            </td>
            <td style="text-align: right; font-weight: 700; color: ${isMasuk ? 'var(--success)' : 'var(--danger)'};">
              ${isMasuk ? '+' : '-'}${it.qty} ${escapeHtml(it.satuan)}
            </td>
            <td><span style="font-size: 0.75rem; color: var(--text-muted);">${escapeHtml(it.keterangan || '-')}</span></td>
          </tr>
        `;
      });

      modalCard.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.08); padding-bottom: 12px;">
          <div>
            <span class="badge ${badgeType}" style="font-size: 0.68rem; margin-bottom: 4px; display: inline-block;">${labelType}</span>
            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--text-main);">${escapeHtml(h.no_transaksi)}</h3>
          </div>
          <button type="button" onclick="closeModal()" style="background: none; border: none; font-size: 1.4rem; color: var(--text-muted); cursor: pointer;" title="Tutup">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <div class="detail-info-grid">
          <div class="detail-info-item">
            <span class="detail-info-label">Tanggal Transaksi</span>
            <span class="detail-info-val">${h.tanggal}</span>
          </div>
          <div class="detail-info-item">
            <span class="detail-info-label">${escapeHtml(h.target_label)}</span>
            <span class="detail-info-val">${escapeHtml(h.target_nama)}</span>
            <span style="font-size: 0.7rem; color: var(--text-muted);">${escapeHtml(h.target_sub)}</span>
          </div>
          <div class="detail-info-item">
            <span class="detail-info-label">${escapeHtml(h.dokumen_label)}</span>
            <span class="detail-info-val">${escapeHtml(h.dokumen_ref || '-')}</span>
          </div>
          <div class="detail-info-item">
            <span class="detail-info-label">Total Qty</span>
            <span class="detail-info-val" style="color: ${isMasuk ? 'var(--success)' : 'var(--danger)'};">
              ${isMasuk ? '+' : '-'}${h.total_qty} (${h.total_item} item)
            </span>
          </div>
        </div>

        ${h.catatan ? `
          <div style="margin-bottom: 14px; padding: 8px 12px; border-radius: 8px; background: rgba(255, 255, 255, 0.04); font-size: 0.78rem; color: var(--text-muted);">
            <strong style="color: var(--text-main);"><i class="bi bi-chat-left-text"></i> Catatan:</strong> ${escapeHtml(h.catatan)}
          </div>
        ` : ''}

        <div style="margin-bottom: 14px;">
          <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-main); margin-bottom: 8px;">
            <i class="bi bi-box-seam"></i> Rincian Item Barang:
          </div>
          <div class="table-responsive" style="max-height: 220px; overflow-y: auto;">
            <table class="modern-table" style="font-size: 0.8rem;">
              <thead>
                <tr>
                  <th style="width: 36px; text-align: center;">#</th>
                  <th>Nama Barang</th>
                  <th style="text-align: right;">Jumlah</th>
                  <th>Rak / Keterangan</th>
                </tr>
              </thead>
              <tbody>
                ${itemsHtml}
              </tbody>
            </table>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-top: 18px;">
          <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal()" style="height: 42px; border-radius: 12px;">
            Tutup
          </button>
          <button type="button" class="btn btn-warning btn-sm" onclick="openEditModal('${type}', ${id})" style="height: 42px; border-radius: 12px; font-weight: 700;">
            <i class="bi bi-pencil-square"></i> Edit Qty
          </button>
          <button type="button" class="btn btn-danger btn-sm" onclick="closeModal(); confirmHapusLaporan('${type}', ${id}, '${escapeHtml(h.no_transaksi)}')" style="height: 42px; border-radius: 12px; font-weight: 700;">
            <i class="bi bi-trash-fill"></i> Hapus
          </button>
        </div>
      `;
    })
    .catch(err => {
      showAlertModal({ title: 'Error', message: 'Gagal memuat detail transaksi: ' + err.message, type: 'danger' });
      closeModal();
    });
}

// 2. MODAL FORM EDIT TRANSAKSI / KOREKSI QTY
function openEditModal(type, id) {
  modalCard.innerHTML = `
    <div style="text-align: center; padding: 40px 0;">
      <i class="bi bi-arrow-repeat spin" style="font-size: 2rem; color: #f59e0b;"></i>
      <p style="margin-top: 10px; color: var(--text-muted); font-size: 0.85rem;">Mempersiapkan form edit...</p>
    </div>
  `;
  modalOverlay.classList.add('active');

  fetch(`laporan.php?ajax=get_detail&type=${type}&id=${id}`)
    .then(res => res.json())
    .then(data => {
      if (data.status !== 'success') {
        showAlertModal({ title: 'Gagal', message: data.message || 'Terjadi kesalahan sistem.', type: 'danger' });
        closeModal();
        return;
      }

      const h = data.header;
      const isMasuk = type === 'masuk';

      let itemsFormHtml = '';
      data.items.forEach((it, idx) => {
        itemsFormHtml += `
          <div style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 12px; padding: 12px; margin-bottom: 10px;">
            <input type="hidden" name="item_id[]" value="${it.id}">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
              <span style="font-weight: 700; color: var(--text-main); font-size: 0.86rem;">
                #${idx + 1} ${escapeHtml(it.nama_barang)}
              </span>
              <span class="stock-status-pill stock-status-safe" style="font-size: 0.68rem;">
                Stok Fisik: ${it.stok_saat_ini} ${escapeHtml(it.satuan)}
              </span>
            </div>

            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 8px;">
              P/N: <strong>${escapeHtml(it.part_number || '-')}</strong> | Kode: ${escapeHtml(it.kode_barang)}
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 10px;">
              <div>
                <label class="ios-label" style="font-size: 0.68rem; margin-bottom: 2px;">Qty (${escapeHtml(it.satuan)}) <span style="color: #ef4444;">*</span></label>
                <input type="number" step="any" min="0.01" name="item_qty[]" value="${it.qty}" class="ios-input" required style="font-weight: 700; text-align: right; height: 38px;">
              </div>
              <div>
                <label class="ios-label" style="font-size: 0.68rem; margin-bottom: 2px;">Keterangan / Lokasi</label>
                <input type="text" name="item_keterangan[]" value="${escapeHtml(it.keterangan || '')}" class="ios-input" placeholder="Kondisi / rak..." style="height: 38px;">
              </div>
            </div>
          </div>
        `;
      });

      modalCard.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid rgba(255, 255, 255, 0.08); padding-bottom: 12px;">
          <div>
            <span class="badge badge-warning" style="font-size: 0.68rem; margin-bottom: 4px; display: inline-block;">KOREKSI TRANSAKSI</span>
            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--text-main);">${escapeHtml(h.no_transaksi)}</h3>
          </div>
          <button type="button" onclick="closeModal()" style="background: none; border: none; font-size: 1.4rem; color: var(--text-muted); cursor: pointer;" title="Batal">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <form action="laporan.php?tgl_mulai=<?= urlencode($tglMulai) ?>&tgl_selesai=<?= urlencode($tglSelesai) ?>&tipe=<?= urlencode($tipe) ?>" method="POST" id="formEditTransaksi">
          <input type="hidden" name="action" value="simpan_edit_transaksi">
          <input type="hidden" name="trans_type" value="${type}">
          <input type="hidden" name="trans_id" value="${id}">

          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 14px;">
            <div>
              <label class="ios-label">Tanggal Transaksi <span style="color: #ef4444;">*</span></label>
              <input type="date" name="tanggal" value="${h.tanggal}" class="ios-input" required>
            </div>
            <div>
              <label class="ios-label">${escapeHtml(h.dokumen_label)}</label>
              <input type="text" name="no_ref" value="${escapeHtml(h.dokumen_ref || '')}" class="ios-input" placeholder="No surat jalan / keperluan...">
            </div>
          </div>

          <div style="margin-bottom: 14px;">
            <label class="ios-label">Catatan Pengiriman / Pengeluaran</label>
            <input type="text" name="catatan" value="${escapeHtml(h.catatan || '')}" class="ios-input" placeholder="Catatan tambahan...">
          </div>

          <div style="margin-bottom: 16px;">
            <label class="ios-label" style="font-weight: 800; color: var(--text-main); margin-bottom: 6px;">
              <i class="bi bi-pencil"></i> Koreksi Jumlah (Qty) Barang:
            </label>
            <div style="max-height: 240px; overflow-y: auto; padding-right: 4px;">
              ${itemsFormHtml}
            </div>
          </div>

          <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-top: 16px;">
            <button type="button" class="maoneart-modal-btn cancel" onclick="closeModal()" style="height: 44px; width: 100%;">
              Batal
            </button>
            <button type="submit" class="maoneart-modal-btn primary" style="height: 44px; width: 100%;">
              <i class="bi bi-check2-circle"></i> Simpan Koreksi
            </button>
          </div>
        </form>
      `;
    })
    .catch(err => {
      showAlertModal({ title: 'Error', message: 'Gagal memuat form edit: ' + err.message, type: 'danger' });
      closeModal();
    });
}

// 3. KONFIRMASI HAPUS TRANSAKSI UNTUK INPUT ULANG
function confirmHapusLaporan(type, id, noTrans) {
  const isMasuk = type === 'masuk';
  const labelTipe = isMasuk ? 'Masuk' : 'Keluar';

  showConfirmModal({
    title: `Hapus Transaksi ${labelTipe}?`,
    message: `Apakah Anda yakin ingin menghapus seluruh transaksi <strong>"${noTrans}"</strong> untuk input ulang?<br><br><strong>Perhatian:</strong> Seluruh stok gudang untuk item terkait akan otomatis dikembalikan ke kondisi semula secara akurat.`,
    confirmText: 'Ya, Hapus & Reset',
    cancelText: 'Batal',
    isDanger: true,
    icon: 'bi-trash-fill',
    onConfirm: () => {
      window.location.href = `laporan.php?action=hapus&type=${type}&id=${id}&tgl_mulai=<?= urlencode($tglMulai) ?>&tgl_selesai=<?= urlencode($tglSelesai) ?>&tipe=<?= urlencode($tipe) ?>`;
    }
  });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
