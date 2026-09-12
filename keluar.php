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
    SELECT b.*, s.singkatan, s.id as def_satuan, sup.nama_supplier 
    FROM barang b 
    LEFT JOIN satuan s ON b.id_satuan = s.id 
    LEFT JOIN supplier sup ON b.id_supplier = sup.id
    ORDER BY b.nama_barang ASC
")->fetchAll();
$satuans = $pdo->query("SELECT * FROM satuan ORDER BY kategori ASC, nama_satuan ASC")->fetchAll();

$autoNoKeluar = generateNoTransaksi('OUT');
$preselectedBarangId = (int) ($_GET['id_barang'] ?? 0);
?>

<!-- iOS Minimalist Header Ala iPhone -->
<div class="ios-top-bar">
  <a href="index.php" class="ios-circle-back" title="Kembali ke Dashboard">
    <i class="bi bi-chevron-left"></i>
  </a>
  <h1 class="ios-bar-title">Barang Keluar</h1>
  <div class="ios-bar-action"></div>
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
        <input type="text" name="no_keluar" class="ios-input" value="<?= htmlspecialchars($autoNoKeluar) ?>" readonly style="color: #dc2626; font-weight: 700;">
      </div>

      <div>
        <label class="ios-label">Tanggal Keluar <span style="color: #ef4444;">*</span></label>
        <input type="date" name="tanggal_keluar" class="ios-input" value="<?= date('Y-m-d') ?>" required>
      </div>

      <div>
        <label class="ios-label">PIC / Teknisi Pengambil <span style="color: #ef4444;">*</span></label>
        <div style="display: flex; gap: 8px;">
          <select name="id_pic" id="selectPic" class="ios-select" required>
            <option value="">-- Pilih PIC / Karyawan --</option>
            <?php foreach ($pics as $p): ?>
              <option value="<?= $p['id'] ?>">
                <?= htmlspecialchars($p['nama_pic']) ?> [<?= htmlspecialchars($p['departemen']) ?>]
              </option>
            <?php endforeach; ?>
          </select>
          <a href="pic.php" class="btn btn-secondary btn-sm" title="Tambah PIC Baru" style="border-radius: 12px; padding: 0 14px; display: inline-flex; align-items: center; justify-content: center;">+</a>
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

    <div id="itemsContainerOut" style="display: flex; flex-direction: column; gap: 14px;">
      <!-- Row 1 -->
      <div class="ios-item-card">
        <div class="item-card-header">
          <span class="item-num-badge" style="color: #ef4444; background: rgba(239, 68, 68, 0.15);"><i class="bi bi-box-arrow-up-right"></i> Item #1</span>
        </div>

        <div class="item-barang-field" style="position: relative;">
          <label class="ios-label">Pilih Barang (Bebas Pilih) <span style="color: #ef4444;">*</span></label>
          <input type="hidden" name="id_barang[]" class="input-id-barang" value="">

          <div class="barang-search-wrapper" style="position: relative;">
            <div style="position: relative; display: flex; align-items: center;">
              <i class="bi bi-search" style="position: absolute; left: 12px; color: var(--text-dim); pointer-events: none; font-size: 0.9rem;"></i>
              <input type="text" 
                     class="ios-input barang-search-input" 
                     placeholder="Ketik nama barang / P/N / kode / rak..." 
                     autocomplete="off" 
                     style="padding-left: 36px; padding-right: 36px;">
              <button type="button" class="btn-clear-barang" style="position: absolute; right: 10px; background: none; border: none; color: var(--text-muted); cursor: pointer; display: none;" title="Reset barang">
                <i class="bi bi-x-circle-fill" style="font-size: 1rem;"></i>
              </button>
            </div>
            <div class="live-search-dropdown" style="display: none;"></div>
          </div>

          <!-- Card Informasi Stok Fisik Awal (Sangat Jelas & Kontras) -->
          <div class="selected-barang-card" style="display: none; flex-direction: column; align-items: stretch; gap: 6px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px;">
              <div style="display: flex; align-items: center; gap: 8px;">
                <span class="stock-status-pill stock-pill-label">Stok Fisik: 0</span>
                <span class="selected-barang-status badge" style="font-size: 0.68rem;">-</span>
              </div>
              <span class="selected-barang-lokasi" style="font-size: 0.72rem; color: var(--text-muted);">Rak: -</span>
            </div>
            <div class="selected-barang-info" style="font-size: 0.75rem; color: var(--text-muted); width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
              -
            </div>
          </div>
        </div>

        <div class="item-qty-satuan-grid">
          <div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <label class="ios-label" style="margin-bottom: 0;">Jumlah Keluar <span style="color: #ef4444;">*</span></label>
              <span class="stock-max-hint" style="font-size: 0.7rem; color: var(--text-muted); font-weight: 600;"></span>
            </div>
            <input type="number" step="any" min="0.01" name="qty[]" class="ios-input item-qty-input" placeholder="0" required style="text-align: right; font-weight: 700; margin-top: 4px;">
            
            <!-- Realtime Alert Box di Bawah Qty -->
            <div class="stock-alert-box danger qty-danger-box" style="display: none;"></div>
            <div class="stock-alert-box warning qty-warning-box" style="display: none;"></div>
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
            <label class="ios-label">Catatan Pemakaian / No. Mesin</label>
            <input type="text" name="item_keterangan[]" class="ios-input" placeholder="No. mesin / keperluan spesifik..." autocomplete="off">
          </div>

          <button type="button" class="item-trash-btn" onclick="removeRowOut(this)" title="Hapus Baris Ini">
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

<script>
// Data Master Barang untuk Client-Side Live Search & Stok Real-time
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

function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Inisialisasi Row Live Search Barang Keluar
function initBarangSearchOut(row) {
  const wrapper = row.querySelector('.barang-search-wrapper');
  const searchInput = row.querySelector('.barang-search-input');
  const idInput = row.querySelector('.input-id-barang');
  const clearBtn = row.querySelector('.btn-clear-barang');
  const dropdown = row.querySelector('.live-search-dropdown');
  const card = row.querySelector('.selected-barang-card');
  const cardPill = row.querySelector('.stock-pill-label');
  const cardStatus = row.querySelector('.selected-barang-status');
  const cardInfo = row.querySelector('.selected-barang-info');
  const cardLokasi = row.querySelector('.selected-barang-lokasi');
  const selectSatuan = row.querySelector('.select-satuan');
  const qtyInput = row.querySelector('.item-qty-input');
  const maxHint = row.querySelector('.stock-max-hint');
  const dangerBox = row.querySelector('.qty-danger-box');
  const warningBox = row.querySelector('.qty-warning-box');

  function renderList(query = '') {
    // PIC bebas pilih barang dari seluruh master barang
    let filtered = ALL_BARANGS;

    const q = query.trim().toLowerCase();
    if (q) {
      filtered = filtered.filter(b => {
        return b.nama.toLowerCase().includes(q) ||
               b.kode.toLowerCase().includes(q) ||
               b.pn.toLowerCase().includes(q) ||
               (b.nama_supplier && b.nama_supplier.toLowerCase().includes(q)) ||
               (b.rak && b.rak.toLowerCase().includes(q));
      });
    }

    if (filtered.length === 0) {
      dropdown.innerHTML = `
        <div style="padding: 14px; text-align: center; color: var(--text-muted); font-size: 0.82rem;">
          <i class="bi bi-inbox" style="font-size: 1.3rem; display: block; margin-bottom: 4px;"></i>
          Tidak ada barang yang cocok dengan "<strong>${escapeHtml(query)}</strong>".
        </div>
      `;
      dropdown.style.display = 'block';
      return;
    }

    let html = '';
    filtered.slice(0, 35).forEach(b => {
      let pillClass = 'stock-status-safe';
      let pillText = `Stok: ${b.stok} ${escapeHtml(b.satuan_nama)}`;

      if (b.stok <= 0) {
        pillClass = 'stock-status-danger';
        pillText = `STOK HABIS (0)`;
      } else if (b.stok <= b.min) {
        pillClass = 'stock-status-warning';
        pillText = `Menipis: ${b.stok} ${escapeHtml(b.satuan_nama)}`;
      }

      html += `
        <div class="search-result-item" data-id="${b.id}">
          <div class="search-item-top">
            <span class="search-item-title">${escapeHtml(b.nama)}</span>
            <span class="stock-status-pill ${pillClass}">${pillText}</span>
          </div>
          <div class="search-item-sub">
            <span>P/N: <strong>${escapeHtml(b.pn || '-')}</strong> | Rak: ${escapeHtml(b.rak || '-')}</span>
            <span>Supplier: ${escapeHtml(b.nama_supplier || '-')}</span>
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

    // Update Status Pill & Badge Stok Fisik Awal
    let pillClass = 'stock-status-safe';
    let pillText = `✓ Stok Tersedia: ${b.stok} ${b.satuan_nama}`;
    let badgeClass = 'badge-success';
    let badgeText = 'STOK AMAN';

    if (b.stok <= 0) {
      pillClass = 'stock-status-danger';
      pillText = `⛔ STOK KOSONG (0 ${b.satuan_nama})`;
      badgeClass = 'badge-danger';
      badgeText = 'HABIS / KOSONG';
    } else if (b.stok <= b.min) {
      pillClass = 'stock-status-warning';
      pillText = `⚠️ Sisa Stok: ${b.stok} ${b.satuan_nama}`;
      badgeClass = 'badge-warning';
      badgeText = `MENIPIS (Min: ${b.min})`;
    }

    cardPill.className = `stock-status-pill ${pillClass} stock-pill-label`;
    cardPill.innerHTML = pillText;

    cardStatus.className = `selected-barang-status badge ${badgeClass}`;
    cardStatus.textContent = badgeText;

    cardInfo.textContent = `P/N: ${b.pn || '-'} | Kode: ${b.kode} | Batas Min: ${b.min} ${b.satuan_nama}`;
    cardLokasi.textContent = b.rak ? `Rak: ${b.rak}` : '';
    card.style.display = 'flex';

    // Update max hint
    maxHint.textContent = `Tersedia: ${b.stok} ${b.satuan_nama}`;

    // Auto set satuan
    if (selectSatuan && b.satuan_id) {
      selectSatuan.value = b.satuan_id;
    }

    // Set max attribute
    qtyInput.setAttribute('max', b.stok);

    // Jalankan validasi stok real-time
    validateRowQty();
  }

  function clearSelection() {
    idInput.value = '';
    searchInput.value = '';
    clearBtn.style.display = 'none';
    card.style.display = 'none';
    dropdown.style.display = 'none';
    maxHint.textContent = '';
    dangerBox.style.display = 'none';
    warningBox.style.display = 'none';
    qtyInput.style.borderColor = '';
    qtyInput.style.boxShadow = '';
    qtyInput.removeAttribute('max');
  }

  function validateRowQty() {
    const bId = parseInt(idInput.value) || 0;
    if (!bId) {
      dangerBox.style.display = 'none';
      warningBox.style.display = 'none';
      qtyInput.style.borderColor = '';
      qtyInput.style.boxShadow = '';
      return;
    }

    const b = ALL_BARANGS.find(item => item.id === bId);
    if (!b) return;

    const qty = parseFloat(qtyInput.value) || 0;

    // Jika stok fisik sudah 0
    if (b.stok <= 0) {
      qtyInput.style.borderColor = '#ef4444';
      qtyInput.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.2)';
      dangerBox.innerHTML = `<i class="bi bi-exclamation-octagon-fill"></i> Stok fisik barang ini KOSONG (0). Tidak dapat dikeluarkan!`;
      dangerBox.style.display = 'flex';
      warningBox.style.display = 'none';
      return;
    }

    // Jika melebihi stok yang ada
    if (qty > b.stok) {
      qtyInput.style.borderColor = '#ef4444';
      qtyInput.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.2)';
      dangerBox.innerHTML = `<i class="bi bi-exclamation-triangle-fill"></i> Melebihi stok gudang! Maksimal tersedia: <strong>${b.stok} ${escapeHtml(b.satuan_nama)}</strong>.`;
      dangerBox.style.display = 'flex';
      warningBox.style.display = 'none';
    } 
    // Jika stok setelah dikurangi akan di bawah batas minimum
    else if (qty > 0 && (b.stok - qty) <= b.min) {
      qtyInput.style.borderColor = '#f59e0b';
      qtyInput.style.boxShadow = '0 0 0 3px rgba(245, 158, 11, 0.2)';
      dangerBox.style.display = 'none';
      const sisa = b.stok - qty;
      warningBox.innerHTML = `<i class="bi bi-info-circle-fill"></i> Sisa stok nantinya tinggal <strong>${sisa} ${escapeHtml(b.satuan_nama)}</strong> (Di bawah batas minimum: ${b.min}).`;
      warningBox.style.display = 'flex';
    } 
    // Stok aman
    else {
      qtyInput.style.borderColor = '';
      qtyInput.style.boxShadow = '';
      dangerBox.style.display = 'none';
      warningBox.style.display = 'none';
    }
  }

  searchInput.addEventListener('focus', () => {
    renderList(searchInput.value);
  });

  searchInput.addEventListener('input', () => {
    if (idInput.value) {
      idInput.value = '';
      card.style.display = 'none';
      clearBtn.style.display = 'none';
      maxHint.textContent = '';
      dangerBox.style.display = 'none';
      warningBox.style.display = 'none';
    }
    renderList(searchInput.value);
  });

  clearBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    clearSelection();
    searchInput.focus();
  });

  qtyInput.addEventListener('input', validateRowQty);

  // Ekspos helper
  row._selectBarang = selectBarang;
  row._clearSelection = clearSelection;
}

// Inisialisasi baris awal
document.addEventListener('DOMContentLoaded', () => {
  const firstRow = document.querySelector('#itemsContainerOut .ios-item-card');
  if (firstRow) {
    initBarangSearchOut(firstRow);
    if (PRESELECTED_BARANG_ID > 0) {
      firstRow._selectBarang(PRESELECTED_BARANG_ID);
    }
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
function createRowHtmlOut() {
  const container = document.getElementById('itemsContainerOut');
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
  newRow.querySelector('.stock-max-hint').textContent = '';
  newRow.querySelector('.qty-danger-box').style.display = 'none';
  newRow.querySelector('.qty-warning-box').style.display = 'none';
  newRow.querySelector('.live-search-dropdown').style.display = 'none';
  newRow.querySelector('.live-search-dropdown').innerHTML = '';
  
  const qtyInp = newRow.querySelector('.item-qty-input');
  qtyInp.style.borderColor = '';
  qtyInp.style.boxShadow = '';
  qtyInp.removeAttribute('max');

  const rows = container.querySelectorAll('.ios-item-card');
  const badge = newRow.querySelector('.item-num-badge');
  if (badge) {
    badge.innerHTML = '<i class="bi bi-box-arrow-up-right"></i> Item #' + (rows.length + 1);
  }

  container.appendChild(newRow);
  initBarangSearchOut(newRow);
}

document.getElementById('btnAddRowOut').addEventListener('click', createRowHtmlOut);

// Hapus Baris
function removeRowOut(btn) {
  const container = document.getElementById('itemsContainerOut');
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
    if (badge) badge.innerHTML = '<i class="bi bi-box-arrow-up-right"></i> Item #' + (idx + 1);
  });
}

// Validasi Form Submit Barang Keluar (Proteksi Total Stok Habis/Minus)
document.getElementById('formKeluar').addEventListener('submit', function(e) {
  const picSelect = document.getElementById('selectPic');
  if (!picSelect.value) {
    e.preventDefault();
    showAlertModal({
      title: 'PIC Belum Dipilih',
      message: 'Harap pilih <strong>PIC / Teknisi Pengambil</strong> barang terlebih dahulu.',
      type: 'danger',
      icon: 'bi-exclamation-octagon-fill'
    });
    picSelect.focus();
    return;
  }

  const rows = document.querySelectorAll('#itemsContainerOut .ios-item-card');
  let hasValidItem = false;

  for (let i = 0; i < rows.length; i++) {
    const row = rows[i];
    const idInput = row.querySelector('.input-id-barang');
    const searchInput = row.querySelector('.barang-search-input');
    const qtyInput = row.querySelector('.item-qty-input');
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
      const b = ALL_BARANGS.find(item => item.id === bId);
      if (!b) continue;

      if (b.stok <= 0) {
        e.preventDefault();
        showAlertModal({
          title: 'Stok Barang Kosong!',
          message: `Barang <strong>${escapeHtml(b.nama)}</strong> pada <strong>Item #${i + 1}</strong> memiliki stok <strong>0 (habis)</strong> di gudang.<br><br>Barang ini tidak dapat dikeluarkan. Silakan hapus baris ini atau lakukan transaksi penerimaan barang terlebih dahulu.`,
          type: 'danger',
          icon: 'bi-exclamation-octagon-fill'
        });
        qtyInput.focus();
        return;
      }

      if (qty <= 0) {
        e.preventDefault();
        showAlertModal({
          title: 'Jumlah Tidak Valid',
          message: `Jumlah qty pengeluaran pada <strong>Item #${i + 1}</strong> (${escapeHtml(b.nama)}) harus lebih dari 0.`,
          type: 'danger',
          icon: 'bi-exclamation-octagon-fill'
        });
        qtyInput.focus();
        return;
      }

      if (qty > b.stok) {
        e.preventDefault();
        showAlertModal({
          title: 'Stok Tidak Mencukupi!',
          message: `Pengeluaran barang <strong>${escapeHtml(b.nama)}</strong> pada <strong>Item #${i + 1}</strong> melebihi sisa stok fisik di gudang!<br><br>Diminta: <strong>${qty} ${escapeHtml(b.satuan_nama)}</strong><br>Stok Tersedia: <strong>${b.stok} ${escapeHtml(b.satuan_nama)}</strong>.<br><br>Harap sesuaikan jumlah yang diminta agar stok tidak minus.`,
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
      message: 'Harap pilih minimal 1 barang yang akan dikeluarkan dari gudang.',
      type: 'danger',
      icon: 'bi-exclamation-octagon-fill'
    });
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
