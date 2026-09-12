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
$preselectedSupplierId = 0;
if ($preselectedBarangId > 0) {
    foreach ($barangs as $b) {
        if ($b['id'] == $preselectedBarangId) {
            $preselectedSupplierId = (int)$b['id_supplier'];
            break;
        }
    }
}

$recentMasuk = $pdo->query("
    SELECT tm.*, s.nama_supplier 
    FROM transaksi_masuk tm 
    JOIN supplier s ON tm.id_supplier = s.id 
    ORDER BY tm.id DESC LIMIT 6
")->fetchAll();
?>

<!-- iOS Minimalist Header Ala iPhone -->
<div class="ios-top-bar">
  <a href="index.php" class="ios-circle-back" title="Kembali ke Dashboard">
    <i class="bi bi-chevron-left"></i>
  </a>
  <h1 class="ios-bar-title">Barang Masuk</h1>
  <div class="ios-bar-action"></div>
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
        <input type="text" name="no_masuk" class="ios-input" value="<?= htmlspecialchars($autoNoMasuk) ?>" readonly style="color: #2563eb; font-weight: 700;">
      </div>

      <div>
        <label class="ios-label">Tanggal Terima <span style="color: #ef4444;">*</span></label>
        <input type="date" name="tanggal_masuk" class="ios-input" value="<?= date('Y-m-d') ?>" required>
      </div>

      <div>
        <label class="ios-label">Supplier Pengirim <span style="color: #ef4444;">*</span></label>
        <div style="display: flex; gap: 8px;">
          <select name="id_supplier" id="selectSupplier" class="ios-select" required>
            <option value="">-- Pilih Supplier Pengirim --</option>
            <?php foreach ($suppliers as $sup): ?>
              <option value="<?= $sup['id'] ?>" <?= $preselectedSupplierId == $sup['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($sup['nama_supplier']) ?> (<?= htmlspecialchars($sup['kode_supplier']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <a href="supplier.php" class="btn btn-secondary btn-sm" title="Tambah Supplier Baru" style="border-radius: 12px; padding: 0 14px; display: inline-flex; align-items: center; justify-content: center;">+</a>
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

    <!-- Banner Info Filter Supplier -->
    <div id="supplierFilterBanner" style="display: none; padding: 8px 12px; background: rgba(37, 99, 235, 0.08); border: 1px solid rgba(37, 99, 235, 0.2); border-radius: 10px; margin-bottom: 12px; font-size: 0.78rem; color: var(--text-main); align-items: center; gap: 8px;">
      <i class="bi bi-funnel-fill text-primary"></i>
      <span>Menampilkan barang khusus dari supplier: <strong id="supplierBannerName" class="text-primary">-</strong></span>
    </div>

    <div id="itemsContainer" style="display: flex; flex-direction: column; gap: 14px;">
      <!-- Row 1 -->
      <div class="ios-item-card">
        <div class="item-card-header">
          <span class="item-num-badge"><i class="bi bi-box-seam"></i> Item #1</span>
        </div>

        <div class="item-barang-field" style="position: relative;">
          <label class="ios-label">Pilih Barang & Part Number <span style="color: #ef4444;">*</span></label>
          <input type="hidden" name="id_barang[]" class="input-id-barang" value="">

          <div class="barang-search-wrapper" style="position: relative;">
            <div style="position: relative; display: flex; align-items: center;">
              <i class="bi bi-search" style="position: absolute; left: 12px; color: var(--text-dim); pointer-events: none; font-size: 0.9rem;"></i>
              <input type="text" 
                     class="ios-input barang-search-input" 
                     placeholder="Ketik nama barang / P/N / kode..." 
                     autocomplete="off" 
                     style="padding-left: 36px; padding-right: 36px;">
              <button type="button" class="btn-clear-barang" style="position: absolute; right: 10px; background: none; border: none; color: var(--text-muted); cursor: pointer; display: none;" title="Reset barang">
                <i class="bi bi-x-circle-fill" style="font-size: 1rem;"></i>
              </button>
            </div>
            <div class="live-search-dropdown" style="display: none;"></div>
          </div>

          <div class="selected-barang-card" style="display: none;">
            <div style="display: flex; align-items: center; gap: 8px; overflow: hidden;">
              <span class="stock-status-pill stock-status-safe stock-pill-label">Stok: 0</span>
              <span class="selected-barang-info" style="font-weight: 600; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">-</span>
            </div>
            <span class="selected-barang-lokasi" style="font-size: 0.72rem; color: var(--text-muted); white-space: nowrap;">-</span>
          </div>
        </div>

        <div class="item-qty-satuan-grid">
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
        </div>

        <div class="item-bottom-action-row">
          <div class="item-notes-field">
            <label class="ios-label">Rak Simpan / Lokasi</label>
            <input type="text" name="item_keterangan[]" class="ios-input" placeholder="Rak simpan / kondisi..." autocomplete="off">
          </div>

          <button type="button" class="item-trash-btn" onclick="removeRow(this)" title="Hapus Baris Ini">
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
              <td><strong class="text-primary"><?= htmlspecialchars($rm['no_masuk']) ?></strong></td>
              <td><?= date('d/m/Y', strtotime($rm['tanggal_masuk'])) ?></td>
              <td><strong style="color: var(--text-main);"><?= htmlspecialchars($rm['nama_supplier']) ?></strong></td>
              <td><?= htmlspecialchars($rm['no_surat_jalan_po'] ?: '-') ?></td>
              <td style="text-align: right; color: var(--success); font-weight: 700;">+<?= formatStok($rm['total_qty']) ?> item</td>
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
// Data Master Barang untuk Client-Side Live Search & Supplier Filtering
const ALL_BARANGS = <?= json_encode(array_map(function($b) {
    return [
        'id' => (int)$b['id'],
        'kode' => $b['kode_barang'] ?? '',
        'nama' => $b['nama_barang'] ?? '',
        'pn' => $b['part_number'] ?? '',
        'id_supplier' => (int)($b['id_supplier'] ?? 0),
        'nama_supplier' => $b['nama_supplier'] ?? '',
        'stok' => (float)$b['stok_saat_ini'],
        'min' => (float)$b['stok_minimum'],
        'satuan_id' => (int)($b['def_satuan'] ?: 1),
        'satuan_nama' => $b['singkatan'] ?? 'PCS',
        'rak' => $b['lokasi_rak'] ?? ''
    ];
}, $barangs)) ?>;

const PRESELECTED_BARANG_ID = <?= $preselectedBarangId ?>;
const selectSupplier = document.getElementById('selectSupplier');
const supplierFilterBanner = document.getElementById('supplierFilterBanner');
const supplierBannerName = document.getElementById('supplierBannerName');

function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function updateSupplierBanner() {
  const selOpt = selectSupplier.options[selectSupplier.selectedIndex];
  if (selectSupplier.value) {
    supplierBannerName.textContent = selOpt.text;
    supplierFilterBanner.style.display = 'flex';
  } else {
    supplierFilterBanner.style.display = 'none';
  }
}

// Inisialisasi Row Live Search
function initBarangSearch(row) {
  const wrapper = row.querySelector('.barang-search-wrapper');
  const searchInput = row.querySelector('.barang-search-input');
  const idInput = row.querySelector('.input-id-barang');
  const clearBtn = row.querySelector('.btn-clear-barang');
  const dropdown = row.querySelector('.live-search-dropdown');
  const card = row.querySelector('.selected-barang-card');
  const cardPill = row.querySelector('.stock-pill-label');
  const cardInfo = row.querySelector('.selected-barang-info');
  const cardLokasi = row.querySelector('.selected-barang-lokasi');
  const selectSatuan = row.querySelector('.select-satuan');
  const inputKet = row.querySelector('input[name="item_keterangan[]"]');

  function renderList(query = '') {
    const supplierId = parseInt(selectSupplier.value) || 0;
    
    // Jika supplier belum dipilih
    if (!supplierId) {
      dropdown.innerHTML = `
        <div style="padding: 14px; text-align: center; color: #f59e0b; font-size: 0.82rem;">
          <i class="bi bi-exclamation-triangle" style="font-size: 1.3rem; display: block; margin-bottom: 4px;"></i>
          Harap pilih <strong>Supplier Pengirim</strong> di atas terlebih dahulu.
        </div>
      `;
      dropdown.style.display = 'block';
      return;
    }

    // Filter barang sesuai supplier yang dipilih
    let filtered = ALL_BARANGS.filter(b => b.id_supplier === supplierId);

    const q = query.trim().toLowerCase();
    if (q) {
      filtered = filtered.filter(b => {
        return b.nama.toLowerCase().includes(q) ||
               b.kode.toLowerCase().includes(q) ||
               b.pn.toLowerCase().includes(q) ||
               (b.rak && b.rak.toLowerCase().includes(q));
      });
    }

    if (filtered.length === 0) {
      const supText = selectSupplier.options[selectSupplier.selectedIndex].text;
      dropdown.innerHTML = `
        <div style="padding: 14px; text-align: center; color: var(--text-muted); font-size: 0.82rem;">
          <i class="bi bi-inbox" style="font-size: 1.3rem; display: block; margin-bottom: 4px;"></i>
          Tidak ada barang dari supplier ini ${q ? `yang cocok "<strong>${escapeHtml(query)}</strong>"` : ''}.
        </div>
      `;
      dropdown.style.display = 'block';
      return;
    }

    let html = '';
    filtered.slice(0, 30).forEach(b => {
      let pillClass = 'stock-status-safe';
      if (b.stok <= 0) pillClass = 'stock-status-danger';
      else if (b.stok <= b.min) pillClass = 'stock-status-warning';

      html += `
        <div class="search-result-item" data-id="${b.id}">
          <div class="search-item-top">
            <span class="search-item-title">${escapeHtml(b.nama)}</span>
            <span class="stock-status-pill ${pillClass}">Stok: ${b.stok} ${escapeHtml(b.satuan_nama)}</span>
          </div>
          <div class="search-item-sub">
            <span>P/N: <strong>${escapeHtml(b.pn || '-')}</strong> | Kode: ${escapeHtml(b.kode)}</span>
            <span>Rak: ${escapeHtml(b.rak || '-')}</span>
          </div>
        </div>
      `;
    });

    dropdown.innerHTML = html;
    dropdown.style.display = 'block';

    // Event Klik Item
    dropdown.querySelectorAll('.search-result-item').forEach(itemEl => {
      itemEl.addEventListener('click', () => {
        const bId = parseInt(itemEl.getAttribute('data-id'));
        selectBarang(bId);
      });
    });
  }

  function selectBarang(bId) {
    const b = ALL_BARANGS.find(item => item.id === bId);
    if (!b) return;

    idInput.value = b.id;
    searchInput.value = b.nama;
    clearBtn.style.display = 'block';
    dropdown.style.display = 'none';

    // Update Card Info
    let pillClass = 'stock-status-safe';
    if (b.stok <= 0) pillClass = 'stock-status-danger';
    else if (b.stok <= b.min) pillClass = 'stock-status-warning';

    cardPill.className = `stock-status-pill ${pillClass} stock-pill-label`;
    cardPill.textContent = `Stok Gudang: ${b.stok} ${b.satuan_nama}`;
    cardInfo.textContent = `P/N: ${b.pn || '-'} | Kode: ${b.kode}`;
    cardLokasi.textContent = b.rak ? `Lokasi: ${b.rak}` : '';
    card.style.display = 'flex';

    // Auto update satuan & keterangan rak
    if (selectSatuan && b.satuan_id) {
      selectSatuan.value = b.satuan_id;
    }
    if (inputKet && !inputKet.value && b.rak) {
      inputKet.value = b.rak;
    }
  }

  function clearSelection() {
    idInput.value = '';
    searchInput.value = '';
    clearBtn.style.display = 'none';
    card.style.display = 'none';
    dropdown.style.display = 'none';
  }

  searchInput.addEventListener('focus', () => {
    renderList(searchInput.value);
  });

  searchInput.addEventListener('input', () => {
    // Jika user mengedit teks, kosongkan id_barang sampai barang dipilih ulang dari list
    if (idInput.value) {
      idInput.value = '';
      card.style.display = 'none';
      clearBtn.style.display = 'none';
    }
    renderList(searchInput.value);
  });

  clearBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    clearSelection();
    searchInput.focus();
  });

  // Ekspos fungsi helper ke element row
  row._selectBarang = selectBarang;
  row._clearSelection = clearSelection;
}

// Initial binding untuk baris pertama
document.addEventListener('DOMContentLoaded', () => {
  const firstRow = document.querySelector('.ios-item-card');
  if (firstRow) {
    initBarangSearch(firstRow);
    if (PRESELECTED_BARANG_ID > 0) {
      firstRow._selectBarang(PRESELECTED_BARANG_ID);
    }
  }
  updateSupplierBanner();
});

// Listener ganti supplier
selectSupplier.addEventListener('change', () => {
  updateSupplierBanner();
  const newSupplierId = parseInt(selectSupplier.value) || 0;
  let resetCount = 0;

  document.querySelectorAll('.ios-item-card').forEach(row => {
    const idInput = row.querySelector('.input-id-barang');
    if (idInput && idInput.value) {
      const b = ALL_BARANGS.find(item => item.id == idInput.value);
      if (b && b.id_supplier !== newSupplierId) {
        if (typeof row._clearSelection === 'function') {
          row._clearSelection();
        }
        resetCount++;
      }
    }
    // Sembunyikan dropdown yang sedang terbuka
    const dropdown = row.querySelector('.live-search-dropdown');
    if (dropdown) dropdown.style.display = 'none';
  });

  if (resetCount > 0) {
    showAlertModal({
      title: 'Daftar Barang Disesuaikan',
      message: `${resetCount} item barang pada baris sebelumnya telah direset karena disesuaikan dengan supplier terpilih.`,
      type: 'info'
    });
  }
});

// Tutup dropdown jika klik di luar
document.addEventListener('click', (e) => {
  if (!e.target.closest('.barang-search-wrapper')) {
    document.querySelectorAll('.live-search-dropdown').forEach(d => {
      d.style.display = 'none';
    });
  }
});

// Tambah Baris Baru
function createRowHtml() {
  const container = document.getElementById('itemsContainer');
  const firstRow = container.querySelector('.ios-item-card');
  const newRow = firstRow.cloneNode(true);

  // Bersihkan data
  newRow.querySelectorAll('input').forEach(inp => {
    if (inp.type !== 'hidden') inp.value = '';
  });
  newRow.querySelector('.input-id-barang').value = '';
  newRow.querySelector('.barang-search-input').value = '';
  newRow.querySelector('.btn-clear-barang').style.display = 'none';
  newRow.querySelector('.selected-barang-card').style.display = 'none';
  newRow.querySelector('.live-search-dropdown').style.display = 'none';
  newRow.querySelector('.live-search-dropdown').innerHTML = '';

  const rows = container.querySelectorAll('.ios-item-card');
  const badge = newRow.querySelector('.item-num-badge');
  if (badge) {
    badge.innerHTML = '<i class="bi bi-box-seam"></i> Item #' + (rows.length + 1);
  }

  container.appendChild(newRow);
  initBarangSearch(newRow);
}

document.getElementById('btnAddRow').addEventListener('click', createRowHtml);

// Hapus Baris
function removeRow(btn) {
  const container = document.getElementById('itemsContainer');
  const rows = container.querySelectorAll('.ios-item-card');
  if (rows.length <= 1) {
    showAlertModal({
      title: 'Perhatian',
      message: 'Minimal harus ada 1 baris barang yang diinput.',
      type: 'info'
    });
    return;
  }
  btn.closest('.ios-item-card').remove();
  
  // Re-number badges
  container.querySelectorAll('.ios-item-card').forEach((row, idx) => {
    const badge = row.querySelector('.item-num-badge');
    if (badge) badge.innerHTML = '<i class="bi bi-box-seam"></i> Item #' + (idx + 1);
  });
}

// Validasi Form Submit Masuk
document.getElementById('formMasuk').addEventListener('submit', function(e) {
  const supplierId = parseInt(selectSupplier.value) || 0;
  if (!supplierId) {
    e.preventDefault();
    showAlertModal({
      title: 'Supplier Belum Dipilih',
      message: 'Harap pilih asal <strong>Supplier Pengirim</strong> terlebih dahulu.',
      type: 'danger',
      icon: 'bi-exclamation-octagon-fill'
    });
    selectSupplier.focus();
    return;
  }

  const rows = document.querySelectorAll('.ios-item-card');
  let hasValidItem = false;

  for (let i = 0; i < rows.length; i++) {
    const row = rows[i];
    const idInput = row.querySelector('.input-id-barang');
    const searchInput = row.querySelector('.barang-search-input');
    const qtyInput = row.querySelector('input[name="qty[]"]');
    const bId = parseInt(idInput.value) || 0;
    const qty = parseFloat(qtyInput.value) || 0;

    if (searchInput.value.trim() && !bId) {
      e.preventDefault();
      showAlertModal({
        title: 'Pilihan Barang Tidak Lengkap',
        message: `Pada <strong>Item #${i + 1}</strong>, Anda mengetik nama barang tetapi belum memilih dari daftar pilihan autocomplete. Silakan klik salah satu barang dari dropdown.`,
        type: 'danger',
        icon: 'bi-exclamation-octagon-fill'
      });
      searchInput.focus();
      return;
    }

    if (bId > 0) {
      if (qty <= 0) {
        e.preventDefault();
        showAlertModal({
          title: 'Jumlah Tidak Valid',
          message: `Jumlah qty pada <strong>Item #${i + 1}</strong> harus lebih dari 0.`,
          type: 'danger',
          icon: 'bi-exclamation-octagon-fill'
        });
        qtyInput.focus();
        return;
      }
      hasValidItem = true;
    }
  }

  if (!hasValidItem) {
    e.preventDefault();
    showAlertModal({
      title: 'Item Masih Kosong',
      message: 'Harap pilih minimal 1 barang yang akan diterima pada form penerimaan.',
      type: 'danger',
      icon: 'bi-exclamation-octagon-fill'
    });
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
