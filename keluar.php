<?php
// keluar.php - Input Pengeluaran Barang (Stock Out) Ala iPhone UI
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

        $stmtOut = $pdo->prepare("
            INSERT INTO transaksi_keluar (no_keluar, id_pic, keperluan, jenis_pengeluaran, tanggal_keluar, total_item, total_qty, catatan, id_user)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtOut->execute([$no_keluar, $id_pic, $keperluan, $jenis_pengeluaran, $tanggal_keluar, $totalItem, $totalQty, $catatan, getUserId()]);
        $idTransKeluar = $pdo->lastInsertId();

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

// Hapus Transaksi Keluar
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
$barangs = $pdo->query("
    SELECT b.*, s.singkatan, s.id as def_satuan 
    FROM barang b 
    LEFT JOIN satuan s ON b.id_satuan = s.id 
    ORDER BY b.nama_barang ASC
")->fetchAll();
$satuans = $pdo->query("SELECT * FROM satuan ORDER BY kategori ASC, nama_satuan ASC")->fetchAll();

$autoNoKeluar = generateNoTransaksi('OUT');
$preselectedBarangId = (int) ($_GET['id_barang'] ?? 0);

$recentKeluar = $pdo->query("
    SELECT tk.*, p.nama_pic, p.departemen 
    FROM transaksi_keluar tk 
    JOIN pic p ON tk.id_pic = p.id 
    ORDER BY tk.id DESC LIMIT 6
")->fetchAll();
?>

<!-- iPhone Style Navigation Header -->
<div class="ios-nav-header">
  <a href="index.php" class="ios-back-btn">
    <i class="bi bi-chevron-left"></i> Kembali
  </a>
  <div class="ios-header-center">
    <h1 class="ios-header-title">Barang Keluar</h1>
    <p class="ios-header-subtitle">Pengeluaran Tools & Part ke PIC Teknisi</p>
  </div>
  <span class="badge badge-danger" style="font-size: 0.7rem;">STOCK OUT</span>
</div>

<form action="keluar.php" method="POST" id="formKeluar">
  <input type="hidden" name="action" value="simpan_keluar">

  <!-- Group 1: Informasi Dokumen & PIC Pengambil -->
  <div class="ios-form-card">
    <div class="ios-group-title">
      <i class="bi bi-person-badge"></i> DOKUMEN & PIC PENGAMBIL
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
      <div>
        <label class="ios-label">No. Transaksi</label>
        <input type="text" name="no_keluar" class="ios-input" value="<?= htmlspecialchars($autoNoKeluar) ?>" readonly style="background: rgba(0,0,0,0.25); color: #f87171; font-weight: 700;">
      </div>

      <div>
        <label class="ios-label">Tanggal Keluar <span style="color: #ef4444;">*</span></label>
        <input type="date" name="tanggal_keluar" class="ios-input" value="<?= date('Y-m-d') ?>" required>
      </div>

      <div>
        <label class="ios-label">PIC / Teknisi Pengambil <span style="color: #ef4444;">*</span></label>
        <div style="display: flex; gap: 8px;">
          <select name="id_pic" class="ios-select" required>
            <option value="">-- Pilih PIC / Karyawan --</option>
            <?php foreach ($pics as $p): ?>
              <option value="<?= $p['id'] ?>">
                <?= htmlspecialchars($p['nama_pic']) ?> [<?= htmlspecialchars($p['departemen']) ?>]
              </option>
            <?php endforeach; ?>
          </select>
          <a href="pic.php" class="btn btn-secondary btn-sm" title="Tambah PIC Baru" style="border-radius: 12px; padding: 0 14px;">+</a>
        </div>
      </div>

      <div>
        <label class="ios-label">Jenis Pemakaian</label>
        <select name="jenis_pengeluaran" class="ios-select">
          <option value="habis_pakai">Barang Habis Pakai (Consumable)</option>
          <option value="peminjaman_tools">Peminjaman Tools / Alat Kerja</option>
        </select>
      </div>
    </div>

    <div style="margin-top: 14px;">
      <label class="ios-label">Keperluan / Lokasi Kerja <span style="color: #ef4444;">*</span></label>
      <input type="text" name="keperluan" class="ios-input" placeholder="Contoh: Perbaikan Mesin Line 2, Pengelasan Frame..." required autocomplete="off">
    </div>
  </div>

  <!-- Group 2: Daftar Item Barang yang Diambil -->
  <div class="ios-form-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
      <div class="ios-group-title" style="margin-bottom: 0;">
        <i class="bi bi-box-arrow-up-right"></i> DAFTAR BARANG YANG DIKELUARKAN
      </div>
      <button type="button" id="btnAddRowOut" class="btn btn-secondary btn-sm" style="border-radius: 999px; padding: 6px 14px; font-size: 0.78rem;">
        <i class="bi bi-plus-lg text-danger"></i> Tambah Item
      </button>
    </div>

    <div id="itemsContainerOut" style="display: flex; flex-direction: column; gap: 12px;">
      <!-- Row 1 -->
      <div class="item-row" style="background: rgba(15, 23, 42, 0.4); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 14px; display: grid; grid-template-columns: 3.5fr 1.2fr 1.5fr 2fr 38px; gap: 10px; align-items: end;">
        <div>
          <label class="ios-label">Pilih Barang & Part Number <span style="color: #ef4444;">*</span></label>
          <select name="id_barang[]" class="ios-select select-barang" required onchange="updateRowDetails(this)">
            <option value="">-- Cari Barang / P/N --</option>
            <?php foreach ($barangs as $b): ?>
              <option value="<?= $b['id'] ?>" data-satuan="<?= $b['def_satuan'] ?>" data-stok="<?= $b['stok_saat_ini'] ?>" <?= $preselectedBarangId == $b['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($b['nama_barang']) ?> [P/N: <?= htmlspecialchars($b['part_number'] ?: '-') ?>] (Sisa: <?= formatStok($b['stok_saat_ini']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="ios-label">Jumlah Keluar <span style="color: #ef4444;">*</span></label>
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
          <label class="ios-label">Catatan Tambahan</label>
          <input type="text" name="item_keterangan[]" class="ios-input" placeholder="No. mesin / kondisi..." autocomplete="off">
        </div>

        <div style="text-align: center;">
          <button type="button" class="btn btn-danger btn-sm" style="border-radius: 10px; width: 38px; height: 38px;" onclick="removeRowOut(this)">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </div>
    </div>

    <div style="margin-top: 16px;">
      <label class="ios-label">Instruksi Khusus / Catatan Tambahan (Opsional)</label>
      <input type="text" name="catatan" class="ios-input" placeholder="Persetujuan spv, perkiraan pengembalian tools...">
    </div>

    <div style="margin-top: 22px;">
      <button type="submit" class="ios-btn-primary ios-btn-red">
        <i class="bi bi-check2-circle" style="font-size: 1.1rem;"></i> Simpan Pengeluaran Barang Keluar
      </button>
    </div>
  </div>
</form>

<!-- Group 3: Riwayat Pengeluaran Terakhir -->
<div class="ios-form-card">
  <div class="ios-group-title">
    <i class="bi bi-clock-history"></i> RIWAYAT PENGELUARAN TERBARU
  </div>

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
                <button type="button" class="btn btn-danger btn-sm" style="border-radius: 8px;" onclick="confirmDelete('keluar.php?action=hapus&id=<?= $rk['id'] ?>', 'Transaksi <?= $rk['no_keluar'] ?>')" title="Batalkan & Kembalikan Stok">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="7" style="text-align: center; color: var(--text-dim); padding: 16px;">Belum ada riwayat pengeluaran.</td></tr>
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

document.getElementById('btnAddRowOut').addEventListener('click', createRowHtmlOut);

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
