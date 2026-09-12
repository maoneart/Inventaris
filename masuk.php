<?php
// masuk.php - Input Pemasukan Barang (Stock In) Ala iPhone UI
$pageTitle = "Input Barang Masuk";
require_once __DIR__ . '/config/database.php';

// Proses Simpan Transaksi Masuk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'simpan_masuk') {
    $no_masuk = trim($_POST['no_masuk'] ?? generateNoTransaksi('IN'));
    $id_supplier = (int) ($_POST['id_supplier'] ?? 0);
    $no_surat_jalan_po = trim($_POST['no_surat_jalan_po'] ?? '');
    $tanggal_masuk = $_POST['tanggal_masuk'] ?? date('Y-m-d');
    $catatan = trim($_POST['catatan'] ?? '');

    $items_barang = $_POST['id_barang'] ?? [];
    $items_qty = $_POST['qty'] ?? [];
    $items_satuan = $_POST['id_satuan'] ?? [];
    $items_ket = $_POST['item_keterangan'] ?? [];

    if ($id_supplier <= 0) {
        setFlash('danger', 'Validasi Gagal', 'Harap pilih asal supplier pengirim barang.');
        header('Location: masuk.php');
        exit;
    }

    if (empty($items_barang)) {
        setFlash('danger', 'Validasi Gagal', 'Minimal harus ada 1 barang yang diterima.');
        header('Location: masuk.php');
        exit;
    }

    try {
        $pdo->beginTransaction();

        $totalItem = 0;
        $totalQty = 0;

        for ($i = 0; $i < count($items_barang); $i++) {
            $bId = (int) $items_barang[$i];
            $qty = (float) $items_qty[$i];
            if ($bId > 0 && $qty > 0) {
                $totalItem++;
                $totalQty += $qty;
            }
        }

        // 1. Simpan Header Transaksi Masuk
        $stmtIn = $pdo->prepare("
            INSERT INTO transaksi_masuk (no_masuk, no_surat_jalan_po, id_supplier, tanggal_masuk, total_item, total_qty, catatan, id_user)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtIn->execute([$no_masuk, $no_surat_jalan_po, $id_supplier, $tanggal_masuk, $totalItem, $totalQty, $catatan, getUserId()]);
        $idTransMasuk = $pdo->lastInsertId();

        // 2. Simpan Detail & Tambah Stok Realtime
        $stmtDetail = $pdo->prepare("
            INSERT INTO detail_transaksi_masuk (id_transaksi_masuk, id_barang, qty, id_satuan, keterangan)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmtUpdateStok = $pdo->prepare("
            UPDATE barang 
            SET stok_saat_ini = stok_saat_ini + ? 
            WHERE id = ?
        ");

        for ($i = 0; $i < count($items_barang); $i++) {
            $bId = (int) $items_barang[$i];
            $qty = (float) $items_qty[$i];
            $satId = (int) ($items_satuan[$i] ?? 1);
            $ket = trim($items_ket[$i] ?? '');

            if ($bId > 0 && $qty > 0) {
                $stmtDetail->execute([$idTransMasuk, $bId, $qty, $satId, $ket]);
                $stmtUpdateStok->execute([$qty, $bId]);
            }
        }

        $pdo->commit();
        setFlash('success', 'Berhasil Disimpan!', "Pemasukan barang <strong>$no_masuk</strong> berhasil dicatat. Stok gudang otomatis bertambah.");
        header('Location: masuk.php');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('danger', 'Gagal Menyimpan', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        header('Location: masuk.php');
        exit;
    }
}

// Hapus Transaksi Masuk
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && isset($_GET['id'])) {
    $delId = (int) $_GET['id'];
    try {
        $pdo->beginTransaction();

        $stmtGet = $pdo->prepare("SELECT id_barang, qty FROM detail_transaksi_masuk WHERE id_transaksi_masuk = ?");
        $stmtGet->execute([$delId]);
        $details = $stmtGet->fetchAll();

        $stmtRevert = $pdo->prepare("UPDATE barang SET stok_saat_ini = GREATEST(0, stok_saat_ini - ?) WHERE id = ?");
        foreach ($details as $d) {
            $stmtRevert->execute([$d['qty'], $d['id_barang']]);
        }

        $stmtDel = $pdo->prepare("DELETE FROM transaksi_masuk WHERE id = ?");
        $stmtDel->execute([$delId]);

        $pdo->commit();
        setFlash('success', 'Data Dihapus', 'Data transaksi masuk berhasil dibatalkan dan stok dikembalikan.');
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('danger', 'Gagal Hapus', $e->getMessage());
    }
    header('Location: masuk.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';

// Data Master
$suppliers = $pdo->query("SELECT * FROM supplier ORDER BY nama_supplier ASC")->fetchAll();
$barangs = $pdo->query("
    SELECT b.*, s.singkatan, s.id as def_satuan, sup.nama_supplier 
    FROM barang b 
    LEFT JOIN satuan s ON b.id_satuan = s.id 
    LEFT JOIN supplier sup ON b.id_supplier = sup.id
    ORDER BY b.nama_barang ASC
")->fetchAll();
$satuans = $pdo->query("SELECT * FROM satuan ORDER BY kategori ASC, nama_satuan ASC")->fetchAll();

$autoNoMasuk = generateNoTransaksi('IN');
$preselectedBarangId = (int) ($_GET['id_barang'] ?? 0);

$recentMasuk = $pdo->query("
    SELECT tm.*, s.nama_supplier 
    FROM transaksi_masuk tm 
    JOIN supplier s ON tm.id_supplier = s.id 
    ORDER BY tm.id DESC LIMIT 6
")->fetchAll();
?>

<!-- Header: Cuma Tombol Back Saja -->
<div class="ios-nav-header-simple">
  <a href="index.php" class="ios-back-btn">
    <i class="bi bi-chevron-left"></i> Kembali
  </a>
</div>

<!-- Judul di Dalam Konten -->
<div class="page-title-box">
  <h1 class="page-title">Barang Masuk</h1>
  <p class="page-subtitle">Penerimaan dari supplier & nomor surat jalan</p>
</div>

<form action="masuk.php" method="POST" id="formMasuk">
  <input type="hidden" name="action" value="simpan_masuk">

  <!-- Group 1: Informasi Dokumen & Pengirim -->
  <div class="ios-form-card">
    <div class="ios-group-title">
      <i class="bi bi-file-earmark-text"></i> DOKUMEN & PENGIRIM
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
      <div>
        <label class="ios-label">No. Transaksi</label>
        <input type="text" name="no_masuk" class="ios-input" value="<?= htmlspecialchars($autoNoMasuk) ?>" readonly style="background: rgba(0,0,0,0.25); color: #60a5fa; font-weight: 700;">
      </div>

      <div>
        <label class="ios-label">Tanggal Terima <span style="color: #ef4444;">*</span></label>
        <input type="date" name="tanggal_masuk" class="ios-input" value="<?= date('Y-m-d') ?>" required>
      </div>

      <div>
        <label class="ios-label">Supplier Pengirim <span style="color: #ef4444;">*</span></label>
        <div style="display: flex; gap: 8px;">
          <select name="id_supplier" class="ios-select" required>
            <option value="">-- Pilih Supplier Pengirim --</option>
            <?php foreach ($suppliers as $sup): ?>
              <option value="<?= $sup['id'] ?>"><?= htmlspecialchars($sup['nama_supplier']) ?> (<?= htmlspecialchars($sup['kode_supplier']) ?>)</option>
            <?php endforeach; ?>
          </select>
          <a href="supplier.php" class="btn btn-secondary btn-sm" title="Tambah Supplier Baru" style="border-radius: 12px; padding: 0 14px;">+</a>
        </div>
      </div>

      <div>
        <label class="ios-label">No. Surat Jalan / PO / Nota</label>
        <input type="text" name="no_surat_jalan_po" class="ios-input" placeholder="Contoh: SJ-2026/09/889" autocomplete="off">
      </div>
    </div>
  </div>

  <!-- Group 2: Daftar Item Barang Masuk -->
  <div class="ios-form-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
      <div class="ios-group-title" style="margin-bottom: 0;">
        <i class="bi bi-box-seam"></i> DAFTAR BARANG YANG DITERIMA
      </div>
      <button type="button" id="btnAddRow" class="btn btn-secondary btn-sm" style="border-radius: 999px; padding: 6px 14px; font-size: 0.78rem;">
        <i class="bi bi-plus-lg text-success"></i> Tambah Item
      </button>
    </div>

    <div id="itemsContainer" style="display: flex; flex-direction: column; gap: 12px;">
      <!-- Row 1 -->
      <div class="item-row" style="background: rgba(15, 23, 42, 0.4); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 14px; display: grid; grid-template-columns: 3.5fr 1.2fr 1.5fr 2fr 38px; gap: 10px; align-items: end;">
        <div>
          <label class="ios-label">Pilih Barang & Part Number <span style="color: #ef4444;">*</span></label>
          <select name="id_barang[]" class="ios-select select-barang" required onchange="updateSatuanRow(this)">
            <option value="">-- Cari Barang / P/N --</option>
            <?php foreach ($barangs as $b): ?>
              <option value="<?= $b['id'] ?>" data-satuan="<?= $b['def_satuan'] ?>" <?= $preselectedBarangId == $b['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($b['nama_barang']) ?> [P/N: <?= htmlspecialchars($b['part_number'] ?: '-') ?>] (Sisa: <?= formatStok($b['stok_saat_ini']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="ios-label">Jumlah (Qty) <span style="color: #ef4444;">*</span></label>
          <input type="number" step="any" min="0.01" name="qty[]" class="ios-input" placeholder="0" required style="text-align: right; font-weight: 700;">
        </div>

        <div>
          <label class="ios-label">Satuan</label>
          <select name="id_satuan[]" class="ios-select select-satuan">
            <?php foreach ($satuans as $s): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['singkatan']) ?> (<?= htmlspecialchars($s['nama_satuan']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="ios-label">Rak Simpan / Catatan</label>
          <input type="text" name="item_keterangan[]" class="ios-input" placeholder="Rak / kondisi..." autocomplete="off">
        </div>

        <div style="text-align: center;">
          <button type="button" class="btn btn-danger btn-sm" style="border-radius: 10px; width: 38px; height: 38px;" onclick="removeRow(this)">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </div>
    </div>

    <div style="margin-top: 16px;">
      <label class="ios-label">Catatan Pengiriman (Opsional)</label>
      <input type="text" name="catatan" class="ios-input" placeholder="Kondisi kemasan, nama driver, dll...">
    </div>

    <div style="margin-top: 22px;">
      <button type="submit" class="ios-btn-primary ios-btn-green">
        <i class="bi bi-check2-circle" style="font-size: 1.1rem;"></i> Simpan Penerimaan Barang Masuk
      </button>
    </div>
  </div>
</form>

<!-- Group 3: Riwayat Penerimaan Terakhir -->
<div class="ios-form-card">
  <div class="ios-group-title">
    <i class="bi bi-clock-history"></i> RIWAYAT PENERIMAAN TERBARU
  </div>

  <div class="table-responsive">
    <table class="modern-table">
      <thead>
        <tr>
          <th>No. Transaksi</th>
          <th>Tanggal</th>
          <th>Supplier</th>
          <th>No. Surat Jalan</th>
          <th style="text-align: right;">Total Qty</th>
          <th style="text-align: center;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($recentMasuk)): ?>
          <?php foreach ($recentMasuk as $rm): ?>
            <tr>
              <td><strong style="color: #60a5fa;"><?= htmlspecialchars($rm['no_masuk']) ?></strong></td>
              <td><?= date('d/m/Y', strtotime($rm['tanggal_masuk'])) ?></td>
              <td><strong style="color: #ffffff;"><?= htmlspecialchars($rm['nama_supplier']) ?></strong></td>
              <td><?= htmlspecialchars($rm['no_surat_jalan_po'] ?: '-') ?></td>
              <td style="text-align: right; color: #34d399; font-weight: 700;">+<?= formatStok($rm['total_qty']) ?> item</td>
              <td style="text-align: center;">
                <button type="button" class="btn btn-danger btn-sm" style="border-radius: 8px;" onclick="confirmDelete('masuk.php?action=hapus&id=<?= $rm['id'] ?>', 'Transaksi <?= $rm['no_masuk'] ?>')" title="Batalkan & Kembalikan Stok">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6" style="text-align: center; color: var(--text-dim); padding: 16px;">Belum ada riwayat penerimaan.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function createRowHtml() {
  const container = document.getElementById('itemsContainer');
  const firstRow = container.querySelector('.item-row');
  const newRow = firstRow.cloneNode(true);

  newRow.querySelectorAll('input').forEach(inp => inp.value = '');
  newRow.querySelector('.select-barang').value = '';
  container.appendChild(newRow);
}

document.getElementById('btnAddRow').addEventListener('click', createRowHtml);

function removeRow(btn) {
  const container = document.getElementById('itemsContainer');
  const rows = container.querySelectorAll('.item-row');
  if (rows.length <= 1) {
    showAlertModal({
      title: 'Perhatian',
      message: 'Minimal harus ada 1 baris barang yang diinput.',
      type: 'info'
    });
    return;
  }
  btn.closest('.item-row').remove();
}

function updateSatuanRow(selectElem) {
  const selectedOption = selectElem.options[selectElem.selectedIndex];
  const satuanId = selectedOption.getAttribute('data-satuan');
  if (satuanId) {
    const row = selectElem.closest('.item-row');
    const satuanSelect = row.querySelector('.select-satuan');
    if (satuanSelect) {
      satuanSelect.value = satuanId;
    }
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
