<?php
// masuk.php - Input Pemasukan Barang (Stock In)
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

        // Hitung total
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

// Hapus Transaksi Masuk (Stok dikembalikan / dikurangi)
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && isset($_GET['id'])) {
    $delId = (int) $_GET['id'];
    try {
        $pdo->beginTransaction();

        // Ambil detail barang untuk kurangi kembali stoknya
        $stmtGet = $pdo->prepare("SELECT id_barang, qty FROM detail_transaksi_masuk WHERE id_transaksi_masuk = ?");
        $stmtGet->execute([$delId]);
        $details = $stmtGet->fetchAll();

        $stmtRevert = $pdo->prepare("UPDATE barang SET stok_saat_ini = GREATEST(0, stok_saat_ini - ?) WHERE id = ?");
        foreach ($details as $d) {
            $stmtRevert->execute([$d['qty'], $d['id_barang']]);
        }

        // Hapus Header (Cascade detail)
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

// Ambil Data Master untuk Dropdown
$suppliers = $pdo->query("SELECT * FROM supplier ORDER BY nama_supplier ASC")->fetchAll();
$barangs = $pdo->query("SELECT b.*, s.singkatan, s.id as def_satuan FROM barang b LEFT JOIN satuan s ON b.id_satuan = s.id ORDER BY b.nama_barang ASC")->fetchAll();
$satuans = $pdo->query("SELECT * FROM satuan ORDER BY kategori ASC, nama_satuan ASC")->fetchAll();

$autoNoMasuk = generateNoTransaksi('IN');
$preselectedBarangId = (int) ($_GET['id_barang'] ?? 0);

// Riwayat Masuk Terbaru
$recentMasuk = $pdo->query("
    SELECT tm.*, s.nama_supplier 
    FROM transaksi_masuk tm 
    JOIN supplier s ON tm.id_supplier = s.id 
    ORDER BY tm.id DESC LIMIT 8
")->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
  <div>
    <h2 style="font-size: 1.25rem; font-weight: 800; color: #34d399; display: flex; align-items: center; gap: 8px;">
      <i class="bi bi-box-arrow-in-down"></i> Input Penerimaan Barang Masuk (Stock In)
    </h2>
    <p style="font-size: 0.78rem; color: var(--text-muted);">Catat kiriman barang dari supplier dan perbarui stok gudang secara instan</p>
  </div>
  <a href="index.php" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Dashboard</a>
</div>

<form action="masuk.php" method="POST" id="formMasuk">
  <input type="hidden" name="action" value="simpan_masuk">

  <!-- Header Surat Jalan & Supplier Card -->
  <div class="glass-card">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
      <div class="form-group">
        <label class="form-label">No. Transaksi Masuk</label>
        <input type="text" name="no_masuk" class="form-control" value="<?= htmlspecialchars($autoNoMasuk) ?>" readonly style="background: rgba(0,0,0,0.3); font-weight: 700; color: #60a5fa;">
      </div>

      <div class="form-group">
        <label class="form-label">Tanggal Terima <span style="color: #ef4444;">*</span></label>
        <input type="date" name="tanggal_masuk" class="form-control" value="<?= date('Y-m-d') ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label">Supplier Pengirim <span style="color: #ef4444;">*</span></label>
        <div style="display: flex; gap: 6px;">
          <select name="id_supplier" class="form-select" required>
            <option value="">-- Pilih Supplier --</option>
            <?php foreach ($suppliers as $sup): ?>
              <option value="<?= $sup['id'] ?>"><?= htmlspecialchars($sup['nama_supplier']) ?> (<?= htmlspecialchars($sup['kode_supplier']) ?>)</option>
            <?php endforeach; ?>
          </select>
          <a href="supplier.php" class="btn btn-secondary btn-sm" title="Tambah Supplier Baru" style="flex-shrink: 0;">+</a>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">No. Surat Jalan / PO / Nota</label>
        <input type="text" name="no_surat_jalan_po" class="form-control" placeholder="Contoh: SJ-2026/09/889" autocomplete="off">
      </div>
    </div>
  </div>

  <!-- Detail Multi-Item Barang Card -->
  <div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <div>
        <h3 style="font-size: 1rem; font-weight: 700; color: #ffffff;">Daftar Barang yang Diterima</h3>
        <p style="font-size: 0.72rem; color: var(--text-muted);">Bisa input lebih dari 1 barang sekaligus dalam 1 pengiriman</p>
      </div>
      <button type="button" id="btnAddRow" class="btn btn-secondary btn-sm">
        <i class="bi bi-plus-circle-fill text-success"></i> + Tambah Baris Barang
      </button>
    </div>

    <div id="itemsContainer" style="display: flex; flex-direction: column; gap: 12px;">
      <!-- Row 1 -->
      <div class="item-row" style="background: rgba(15, 23, 42, 0.5); border: 1px solid var(--card-border); border-radius: 12px; padding: 14px; display: grid; grid-template-columns: 3fr 1.2fr 1.5fr 2fr 40px; gap: 10px; align-items: end;">
        <div>
          <label class="form-label">Pilih Barang <span style="color: #ef4444;">*</span></label>
          <select name="id_barang[]" class="form-select select-barang" required onchange="updateSatuanRow(this)">
            <option value="">-- Cari Barang --</option>
            <?php foreach ($barangs as $b): ?>
              <option value="<?= $b['id'] ?>" data-satuan="<?= $b['def_satuan'] ?>" <?= $preselectedBarangId == $b['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($b['nama_barang']) ?> [<?= htmlspecialchars($b['kode_barang']) ?>] (Stok: <?= formatStok($b['stok_saat_ini']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="form-label">Jumlah (Qty) <span style="color: #ef4444;">*</span></label>
          <input type="number" step="any" min="0.01" name="qty[]" class="form-control text-right" placeholder="0" required style="text-align: right; font-weight: 700;">
        </div>

        <div>
          <label class="form-label">Satuan</label>
          <select name="id_satuan[]" class="form-select select-satuan">
            <?php foreach ($satuans as $s): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['singkatan']) ?> (<?= htmlspecialchars($s['nama_satuan']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="form-label">Catatan / Rak Simpan</label>
          <input type="text" name="item_keterangan[]" class="form-control" placeholder="Kondisi barang / rak..." autocomplete="off">
        </div>

        <div style="text-align: center;">
          <button type="button" class="btn btn-danger btn-sm btn-remove-row" style="padding: 10px; width: 38px; height: 38px;" onclick="removeRow(this)">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </div>
    </div>

    <div style="margin-top: 16px;">
      <label class="form-label">Catatan Pengiriman Keseluruhan (Opsional)</label>
      <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan sopir, kondisi packaging, dll..."></textarea>
    </div>

    <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
      <a href="index.php" class="btn btn-secondary">Batal</a>
      <button type="submit" class="btn btn-success" style="padding: 12px 24px; font-size: 0.95rem;">
        <i class="bi bi-check2-circle"></i> Simpan Penerimaan Barang Masuk
      </button>
    </div>
  </div>
</form>

<!-- Riwayat Pemasukan Terakhir -->
<div class="glass-card">
  <h3 style="font-size: 1rem; font-weight: 700; color: #ffffff; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
    <i class="bi bi-clock-history text-success"></i> Riwayat Pemasukan Terbaru
  </h3>
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
                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete('masuk.php?action=hapus&id=<?= $rm['id'] ?>', 'Transaksi <?= $rm['no_masuk'] ?>')" title="Batalkan Transaksi & Kembalikan Stok">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" style="text-align: center; color: var(--text-dim); padding: 16px;">Belum ada riwayat penerimaan.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
// Template baris barang baru
function createRowHtml() {
  const container = document.getElementById('itemsContainer');
  const firstRow = container.querySelector('.item-row');
  const newRow = firstRow.cloneNode(true);

  // Reset values
  newRow.querySelectorAll('input').forEach(inp => inp.value = '');
  newRow.querySelector('.select-barang').value = '';
  container.appendChild(newRow);
}

document.getElementById('btnAddRow').addEventListener('click', () => {
  createRowHtml();
});

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
