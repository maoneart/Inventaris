<?php
// keluar.php - Input Pengeluaran Barang (Stock Out)
$pageTitle = "Input Barang Keluar";
require_once __DIR__ . '/config/database.php';

// Simpan Transaksi Keluar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'simpan_keluar') {
    $no_keluar = trim($_POST['no_keluar'] ?? generateNoTransaksi('OUT'));
    $id_pic = (int) ($_POST['id_pic'] ?? 0);
    $keperluan = trim($_POST['keperluan'] ?? '');
    $jenis_pengeluaran = $_POST['jenis_pengeluaran'] ?? 'habis_pakai';
    $tanggal_keluar = $_POST['tanggal_keluar'] ?? date('Y-m-d');
    $catatan = trim($_POST['catatan'] ?? '');

    $items_barang = $_POST['id_barang'] ?? [];
    $items_qty = $_POST['qty'] ?? [];
    $items_satuan = $_POST['id_satuan'] ?? [];
    $items_ket = $_POST['item_keterangan'] ?? [];

    if ($id_pic <= 0) {
        setFlash('danger', 'Validasi Gagal', 'Harap pilih PIC / Karyawan yang mengambil barang/tools.');
        header('Location: keluar.php');
        exit;
    }

    if (empty($items_barang)) {
        setFlash('danger', 'Validasi Gagal', 'Minimal harus ada 1 barang yang dikeluarkan.');
        header('Location: keluar.php');
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Cek Ketersediaan Stok Tiap Barang
        $stmtCek = $pdo->prepare("SELECT nama_barang, stok_saat_ini FROM barang WHERE id = ? FOR UPDATE");
        for ($i = 0; $i < count($items_barang); $i++) {
            $bId = (int) $items_barang[$i];
            $qty = (float) $items_qty[$i];
            if ($bId > 0 && $qty > 0) {
                $stmtCek->execute([$bId]);
                $bRow = $stmtCek->fetch();
                if (!$bRow) {
                    throw new Exception("Barang ID $bId tidak ditemukan.");
                }
                if ($bRow['stok_saat_ini'] < $qty) {
                    throw new Exception("Stok untuk <strong>{$bRow['nama_barang']}</strong> tidak mencukupi! Sisa stok: {$bRow['stok_saat_ini']}, diminta: $qty.");
                }
            }
        }

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

        // 2. Simpan Header Pengeluaran
        $stmtOut = $pdo->prepare("
            INSERT INTO transaksi_keluar (no_keluar, id_pic, keperluan, jenis_pengeluaran, tanggal_keluar, total_item, total_qty, catatan, id_user)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtOut->execute([$no_keluar, $id_pic, $keperluan, $jenis_pengeluaran, $tanggal_keluar, $totalItem, $totalQty, $catatan, getUserId()]);
        $idTransKeluar = $pdo->lastInsertId();

        // 3. Simpan Detail & Kurangi Stok Realtime
        $stmtDetail = $pdo->prepare("
            INSERT INTO detail_transaksi_keluar (id_transaksi_keluar, id_barang, qty, id_satuan, keterangan)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmtKurangStok = $pdo->prepare("
            UPDATE barang 
            SET stok_saat_ini = stok_saat_ini - ? 
            WHERE id = ?
        ");

        for ($i = 0; $i < count($items_barang); $i++) {
            $bId = (int) $items_barang[$i];
            $qty = (float) $items_qty[$i];
            $satId = (int) ($items_satuan[$i] ?? 1);
            $ket = trim($items_ket[$i] ?? '');

            if ($bId > 0 && $qty > 0) {
                $stmtDetail->execute([$idTransKeluar, $bId, $qty, $satId, $ket]);
                $stmtKurangStok->execute([$qty, $bId]);
            }
        }

        $pdo->commit();
        setFlash('success', 'Berhasil Disimpan!', "Pengeluaran barang <strong>$no_keluar</strong> berhasil dicatat. Stok gudang otomatis dikurangi.");
        header('Location: keluar.php');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('danger', 'Gagal Mengeluarkan Stok', $e->getMessage());
        header('Location: keluar.php');
        exit;
    }
}

// Hapus Transaksi Keluar (Stok dikembalikan / ditambah kembali)
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && isset($_GET['id'])) {
    $delId = (int) $_GET['id'];
    try {
        $pdo->beginTransaction();

        $stmtGet = $pdo->prepare("SELECT id_barang, qty FROM detail_transaksi_keluar WHERE id_transaksi_keluar = ?");
        $stmtGet->execute([$delId]);
        $details = $stmtGet->fetchAll();

        $stmtRevert = $pdo->prepare("UPDATE barang SET stok_saat_ini = stok_saat_ini + ? WHERE id = ?");
        foreach ($details as $d) {
            $stmtRevert->execute([$d['qty'], $d['id_barang']]);
        }

        $stmtDel = $pdo->prepare("DELETE FROM transaksi_keluar WHERE id = ?");
        $stmtDel->execute([$delId]);

        $pdo->commit();
        setFlash('success', 'Data Dihapus', 'Data transaksi keluar berhasil dibatalkan dan stok dikembalikan ke gudang.');
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('danger', 'Gagal Hapus', $e->getMessage());
    }
    header('Location: keluar.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';

$pics = $pdo->query("SELECT * FROM pic ORDER BY departemen ASC, nama_pic ASC")->fetchAll();
$barangs = $pdo->query("SELECT b.*, s.singkatan, s.id as def_satuan FROM barang b LEFT JOIN satuan s ON b.id_satuan = s.id ORDER BY b.nama_barang ASC")->fetchAll();
$satuans = $pdo->query("SELECT * FROM satuan ORDER BY kategori ASC, nama_satuan ASC")->fetchAll();

$autoNoKeluar = generateNoTransaksi('OUT');
$preselectedBarangId = (int) ($_GET['id_barang'] ?? 0);

$recentKeluar = $pdo->query("
    SELECT tk.*, p.nama_pic, p.departemen 
    FROM transaksi_keluar tk 
    JOIN pic p ON tk.id_pic = p.id 
    ORDER BY tk.id DESC LIMIT 8
")->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
  <div>
    <h2 style="font-size: 1.25rem; font-weight: 800; color: #f87171; display: flex; align-items: center; gap: 8px;">
      <i class="bi bi-box-arrow-up-right"></i> Input Pengeluaran Barang / Tools (Stock Out)
    </h2>
    <p style="font-size: 0.78rem; color: var(--text-muted);">Catat barang atau peralatan kerja yang diambil oleh PIC / Karyawan / Teknisi</p>
  </div>
  <a href="index.php" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Dashboard</a>
</div>

<form action="keluar.php" method="POST" id="formKeluar">
  <input type="hidden" name="action" value="simpan_keluar">

  <!-- Header PIC & Keperluan Card -->
  <div class="glass-card">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
      <div class="form-group">
        <label class="form-label">No. Transaksi Keluar</label>
        <input type="text" name="no_keluar" class="form-control" value="<?= htmlspecialchars($autoNoKeluar) ?>" readonly style="background: rgba(0,0,0,0.3); font-weight: 700; color: #f87171;">
      </div>

      <div class="form-group">
        <label class="form-label">Tanggal Keluar <span style="color: #ef4444;">*</span></label>
        <input type="date" name="tanggal_keluar" class="form-control" value="<?= date('Y-m-d') ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label">PIC / Teknisi Pengambil <span style="color: #ef4444;">*</span></label>
        <div style="display: flex; gap: 6px;">
          <select name="id_pic" class="form-select" required>
            <option value="">-- Pilih PIC / Karyawan --</option>
            <?php foreach ($pics as $p): ?>
              <option value="<?= $p['id'] ?>">
                <?= htmlspecialchars($p['nama_pic']) ?> [<?= htmlspecialchars($p['departemen']) ?> - <?= htmlspecialchars($p['jabatan']) ?>]
              </option>
            <?php endforeach; ?>
          </select>
          <a href="pic.php" class="btn btn-secondary btn-sm" title="Tambah PIC Baru" style="flex-shrink: 0;">+</a>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Jenis Pengeluaran</label>
        <select name="jenis_pengeluaran" class="form-select">
          <option value="habis_pakai">Barang Habis Pakai (Consumable)</option>
          <option value="peminjaman_tools">Peminjaman Tools / Perkakas Kerja</option>
        </select>
      </div>
    </div>

    <div class="form-group" style="margin-top: 4px; margin-bottom: 0;">
      <label class="form-label">Keperluan Pengambilan / Lokasi Kerja <span style="color: #ef4444;">*</span></label>
      <input type="text" name="keperluan" class="form-control" placeholder="Contoh: Perbaikan Mesin Injection Line 2, Pengelasan Frame Proyek B..." required autocomplete="off">
    </div>
  </div>

  <!-- Detail Multi-Item Barang Keluar -->
  <div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <div>
        <h3 style="font-size: 1rem; font-weight: 700; color: #ffffff;">Daftar Barang / Tools yang Diambil</h3>
        <p style="font-size: 0.72rem; color: var(--text-muted);">Jumlah yang diambil otomatis mengurangi stok gudang realtime</p>
      </div>
      <button type="button" id="btnAddRowOut" class="btn btn-secondary btn-sm">
        <i class="bi bi-plus-circle-fill text-danger"></i> + Tambah Baris Barang
      </button>
    </div>

    <div id="itemsContainerOut" style="display: flex; flex-direction: column; gap: 12px;">
      <!-- Row 1 -->
      <div class="item-row" style="background: rgba(15, 23, 42, 0.5); border: 1px solid var(--card-border); border-radius: 12px; padding: 14px; display: grid; grid-template-columns: 3fr 1.2fr 1.5fr 2fr 40px; gap: 10px; align-items: end;">
        <div>
          <label class="form-label">Pilih Barang / Alat <span style="color: #ef4444;">*</span></label>
          <select name="id_barang[]" class="form-select select-barang" required onchange="updateRowDetails(this)">
            <option value="">-- Pilih Barang --</option>
            <?php foreach ($barangs as $b): ?>
              <option value="<?= $b['id'] ?>" data-satuan="<?= $b['def_satuan'] ?>" data-stok="<?= $b['stok_saat_ini'] ?>" <?= $preselectedBarangId == $b['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($b['nama_barang']) ?> (Sisa: <?= formatStok($b['stok_saat_ini']) ?> <?= htmlspecialchars($b['singkatan']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="form-label">Jumlah Keluar <span style="color: #ef4444;">*</span></label>
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
          <label class="form-label">Catatan Tambahan (Opsional)</label>
          <input type="text" name="item_keterangan[]" class="form-control" placeholder="Kondisi pinjam / nomor part..." autocomplete="off">
        </div>

        <div style="text-align: center;">
          <button type="button" class="btn btn-danger btn-sm btn-remove-row" style="padding: 10px; width: 38px; height: 38px;" onclick="removeRowOut(this)">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </div>
    </div>

    <div style="margin-top: 16px;">
      <label class="form-label">Catatan Pengeluaran / Instruksi Khusus (Opsional)</label>
      <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan persetujuan supervisor, estimasi pengembalian tools, dll..."></textarea>
    </div>

    <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
      <a href="index.php" class="btn btn-secondary">Batal</a>
      <button type="submit" class="btn btn-danger" style="padding: 12px 24px; font-size: 0.95rem;">
        <i class="bi bi-check2-circle"></i> Simpan Pengeluaran Barang Keluar
      </button>
    </div>
  </div>
</form>

<!-- Riwayat Pengeluaran Terakhir -->
<div class="glass-card">
  <h3 style="font-size: 1rem; font-weight: 700; color: #ffffff; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
    <i class="bi bi-clock-history text-danger"></i> Riwayat Pengeluaran Terbaru
  </h3>
  <div class="table-responsive">
    <table class="modern-table">
      <thead>
        <tr>
          <th>No. Transaksi</th>
          <th>Tanggal</th>
          <th>PIC Pengambil</th>
          <th>Departemen</th>
          <th>Keperluan</th>
          <th style="text-align: right;">Total Qty</th>
          <th style="text-align: center;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($recentKeluar)): ?>
          <?php foreach ($recentKeluar as $rk): ?>
            <tr>
              <td><strong style="color: #60a5fa;"><?= htmlspecialchars($rk['no_keluar']) ?></strong></td>
              <td><?= date('d/m/Y', strtotime($rk['tanggal_keluar'])) ?></td>
              <td><strong style="color: #ffffff;"><?= htmlspecialchars($rk['nama_pic']) ?></strong></td>
              <td><span class="badge badge-purple"><?= htmlspecialchars($rk['departemen']) ?></span></td>
              <td><span style="font-size: 0.8rem; color: #cbd5e1;"><?= htmlspecialchars($rk['keperluan']) ?></span></td>
              <td style="text-align: right; color: #f87171; font-weight: 700;">-<?= formatStok($rk['total_qty']) ?> item</td>
              <td style="text-align: center;">
                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete('keluar.php?action=hapus&id=<?= $rk['id'] ?>', 'Transaksi <?= $rk['no_keluar'] ?>')" title="Batalkan Transaksi & Kembalikan Stok">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" style="text-align: center; color: var(--text-dim); padding: 16px;">Belum ada riwayat pengeluaran.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function createRowHtmlOut() {
  const container = document.getElementById('itemsContainerOut');
  const firstRow = container.querySelector('.item-row');
  const newRow = firstRow.cloneNode(true);

  newRow.querySelectorAll('input').forEach(inp => inp.value = '');
  newRow.querySelector('.select-barang').value = '';
  container.appendChild(newRow);
}

document.getElementById('btnAddRowOut').addEventListener('click', () => {
  createRowHtmlOut();
});

function removeRowOut(btn) {
  const container = document.getElementById('itemsContainerOut');
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

function updateRowDetails(selectElem) {
  const selectedOption = selectElem.options[selectElem.selectedIndex];
  const satuanId = selectedOption.getAttribute('data-satuan');
  const maxStok = parseFloat(selectedOption.getAttribute('data-stok')) || 0;
  
  const row = selectElem.closest('.item-row');
  const satuanSelect = row.querySelector('.select-satuan');
  const qtyInput = row.querySelector('input[name="qty[]"]');

  if (satuanId && satuanSelect) {
    satuanSelect.value = satuanId;
  }
  if (qtyInput) {
    qtyInput.setAttribute('max', maxStok);
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
