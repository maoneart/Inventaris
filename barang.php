<?php
// barang.php - Master Data Barang, Part Number & Supplier
$pageTitle = "Master Data Barang & Part Number";
require_once __DIR__ . '/config/database.php';

// Proses Simpan / Update Barang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'tambah_barang') {
        $kode_barang = trim($_POST['kode_barang'] ?? '');
        $part_number = trim($_POST['part_number'] ?? '');
        $barcode = trim($_POST['barcode'] ?? '');
        $nama_barang = trim($_POST['nama_barang'] ?? '');
        $id_kategori = (int) ($_POST['id_kategori'] ?? 1);
        $id_supplier = !empty($_POST['id_supplier']) ? (int)$_POST['id_supplier'] : null;
        $id_satuan = (int) ($_POST['id_satuan'] ?? 1);
        $stok_saat_ini = (float) ($_POST['stok_awal'] ?? 0);
        $stok_minimum = (float) ($_POST['stok_minimum'] ?? 5);
        $lokasi_rak = trim($_POST['lokasi_rak'] ?? 'Rak Umum');
        $spesifikasi = trim($_POST['spesifikasi'] ?? '');

        if (empty($kode_barang) || empty($nama_barang)) {
            setFlash('danger', 'Validasi Gagal', 'Kode Barang dan Nama Barang wajib diisi.');
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO barang (kode_barang, part_number, barcode, nama_barang, id_kategori, id_supplier, id_satuan, stok_saat_ini, stok_minimum, lokasi_rak, spesifikasi)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$kode_barang, $part_number, $barcode, $nama_barang, $id_kategori, $id_supplier, $id_satuan, $stok_saat_ini, $stok_minimum, $lokasi_rak, $spesifikasi]);
                setFlash('success', 'Barang Ditambahkan', "Barang <strong>$nama_barang</strong> (P/N: $part_number) berhasil didaftarkan.");
            } catch (PDOException $e) {
                if ($e->errorInfo[1] == 1062) {
                    setFlash('danger', 'Kode Duplikat', "Kode barang <strong>$kode_barang</strong> sudah terdaftar di sistem.");
                } else {
                    setFlash('danger', 'Gagal Tambah Barang', $e->getMessage());
                }
            }
        }
        header('Location: barang.php');
        exit;
    }

    if ($_POST['action'] === 'tambah_satuan') {
        $nama_satuan = trim($_POST['nama_satuan'] ?? '');
        $singkatan = trim($_POST['singkatan'] ?? '');
        $kategori_satuan = $_POST['kategori_satuan'] ?? 'unit';

        if (!empty($nama_satuan) && !empty($singkatan)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO satuan (nama_satuan, singkatan, kategori) VALUES (?, ?, ?)");
                $stmt->execute([$nama_satuan, $singkatan, $kategori_satuan]);
                setFlash('success', 'Satuan Ditambahkan', "Satuan <strong>$singkatan ($nama_satuan)</strong> siap digunakan.");
            } catch (Exception $e) {
                setFlash('danger', 'Gagal Tambah Satuan', $e->getMessage());
            }
        }
        header('Location: barang.php');
        exit;
    }
}

// Hapus Barang
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM barang WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Data Dihapus', 'Data barang berhasil dihapus dari master inventaris.');
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1451) {
            setFlash('danger', 'Tidak Bisa Dihapus', 'Barang ini sudah memiliki riwayat transaksi masuk/keluar. Tidak dapat dihapus demi integritas audit.');
        } else {
            setFlash('danger', 'Gagal Hapus', $e->getMessage());
        }
    }
    header('Location: barang.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';

// Data Dropdown
$kategoris = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC")->fetchAll();
$suppliers = $pdo->query("SELECT * FROM supplier ORDER BY nama_supplier ASC")->fetchAll();
$satuans = $pdo->query("SELECT * FROM satuan ORDER BY kategori ASC, nama_satuan ASC")->fetchAll();

// Daftar Barang dengan Join Supplier & Kategori
$barangs = $pdo->query("
    SELECT b.*, k.nama_kategori, s.nama_satuan, s.singkatan, sup.nama_supplier, sup.kode_supplier
    FROM barang b
    LEFT JOIN kategori k ON b.id_kategori = k.id
    LEFT JOIN satuan s ON b.id_satuan = s.id
    LEFT JOIN supplier sup ON b.id_supplier = sup.id
    ORDER BY b.nama_barang ASC
")->fetchAll();

$stmtNext = $pdo->query("SELECT COUNT(*) + 1 FROM barang");
$nextNum = $stmtNext->fetchColumn();
$saranKode = 'BRG-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <h2 style="font-size: 1.25rem; font-weight: 800; color: #ffffff; display: flex; align-items: center; gap: 8px;">
      <i class="bi bi-boxes text-info"></i> Master Data Barang, Part Number & Rekanan Supplier
    </h2>
    <p style="font-size: 0.78rem; color: var(--text-muted);">Kelola katalog barang, Part Number (P/N), asal supplier, satuan lengkap, dan stok fisik</p>
  </div>
  <div style="display: flex; gap: 8px;">
    <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('modalSatuan').style.display='flex'">
      <i class="bi bi-tag-fill text-warning"></i> + Tambah Satuan
    </button>
    <a href="supplier.php" class="btn btn-secondary btn-sm">
      <i class="bi bi-truck text-success"></i> Kelola Supplier
    </a>
  </div>
</div>

<!-- Form Pendaftaran Barang Baru Card -->
<div class="glass-card">
  <h3 style="font-size: 1rem; font-weight: 700; color: #60a5fa; margin-bottom: 14px; display: flex; align-items: center; gap: 6px;">
    <i class="bi bi-plus-square-fill"></i> Daftarkan Barang Baru & Part Number
  </h3>

  <form action="barang.php" method="POST">
    <input type="hidden" name="action" value="tambah_barang">

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px;">
      <div class="form-group">
        <label class="form-label">Kode Barang Sistem <span style="color: #ef4444;">*</span></label>
        <input type="text" name="kode_barang" class="form-control" value="<?= htmlspecialchars($saranKode) ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label">Part Number (P/N) Pabrik <span style="color: #ef4444;">*</span></label>
        <input type="text" name="part_number" class="form-control" placeholder="Contoh: WR-CRV-824, GWS-060..." required style="font-weight: 700; color: #93c5fd;">
      </div>

      <div class="form-group">
        <label class="form-label">Barcode / SKU (Opsional)</label>
        <input type="text" name="barcode" class="form-control" placeholder="Contoh: 8991234567">
      </div>

      <div class="form-group" style="grid-column: span 2;">
        <label class="form-label">Nama Barang / Peralatan <span style="color: #ef4444;">*</span></label>
        <input type="text" name="nama_barang" class="form-control" placeholder="Contoh: Kunci Pas Ring 17mm, Kawat Las RD-260..." required>
      </div>

      <div class="form-group">
        <label class="form-label">Supplier Utama (Asal Pemasok)</label>
        <select name="id_supplier" class="form-select">
          <option value="">-- Pilih Supplier Rekanan --</option>
          <?php foreach ($suppliers as $sup): ?>
            <option value="<?= $sup['id'] ?>"><?= htmlspecialchars($sup['nama_supplier']) ?> (<?= htmlspecialchars($sup['kode_supplier']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Kategori <span style="color: #ef4444;">*</span></label>
        <select name="id_kategori" class="form-select" required>
          <?php foreach ($kategoris as $k): ?>
            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Satuan Utama <span style="color: #ef4444;">*</span></label>
        <select name="id_satuan" class="form-select" required>
          <?php foreach ($satuans as $s): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['singkatan']) ?> (<?= htmlspecialchars($s['nama_satuan']) ?> - <?= ucfirst($s['kategori']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Stok Awal Fisik</label>
        <input type="number" step="any" min="0" name="stok_awal" class="form-control" value="0">
      </div>

      <div class="form-group">
        <label class="form-label">Batas Stok Minimum (Warning)</label>
        <input type="number" step="any" min="0" name="stok_minimum" class="form-control" value="5">
      </div>

      <div class="form-group">
        <label class="form-label">Lokasi Rak / Gudang</label>
        <input type="text" name="lokasi_rak" class="form-control" placeholder="Contoh: Rak A-02, Bin C-12, Area Drum">
      </div>

      <div class="form-group" style="grid-column: span 2;">
        <label class="form-label">Spesifikasi / Detail Part</label>
        <input type="text" name="spesifikasi" class="form-control" placeholder="Ukuran, diameter, voltase, grade baja...">
      </div>
    </div>

    <div style="margin-top: 10px; display: flex; justify-content: flex-end;">
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-save-fill"></i> Simpan Barang & Part Number
      </button>
    </div>
  </form>
</div>

<!-- Modal Tambah Custom Satuan -->
<div id="modalSatuan" class="maoneart-modal-overlay" style="display: none;">
  <div class="maoneart-modal-card" style="max-width: 420px; text-align: left;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="font-size: 1.1rem; font-weight: 800; color: #ffffff;">+ Tambah Satuan Baru</h3>
      <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('modalSatuan').style.display='none'">✕</button>
    </div>

    <form action="barang.php" method="POST">
      <input type="hidden" name="action" value="tambah_satuan">

      <div class="form-group">
        <label class="form-label">Nama Lengkap Satuan</label>
        <input type="text" name="nama_satuan" class="form-control" placeholder="Contoh: Centimeter Kubik, Slop, Rim..." required>
      </div>

      <div class="form-group">
        <label class="form-label">Singkatan Satuan</label>
        <input type="text" name="singkatan" class="form-control" placeholder="Contoh: cm3, slp, rim..." required>
      </div>

      <div class="form-group">
        <label class="form-label">Kategori Satuan</label>
        <select name="kategori_satuan" class="form-select">
          <option value="unit">Unit / Kuantitas (pcs, buah, unit)</option>
          <option value="berat">Berat (kg, gram, ton)</option>
          <option value="volume">Volume (liter, ml, drum)</option>
          <option value="panjang">Panjang / Dimensi (meter, roll)</option>
          <option value="kemasan">Kemasan (box, dus, karung)</option>
          <option value="lainnya">Lainnya</option>
        </select>
      </div>

      <div class="maoneart-modal-actions" style="margin-top: 20px;">
        <button type="button" class="maoneart-modal-btn cancel" onclick="document.getElementById('modalSatuan').style.display='none'">Batal</button>
        <button type="submit" class="maoneart-modal-btn primary">Simpan Satuan</button>
      </div>
    </form>
  </div>
</div>

<!-- Daftar Master Barang & Part Number -->
<div class="glass-card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; gap: 12px; flex-wrap: wrap;">
    <h3 style="font-size: 1rem; font-weight: 700; color: #ffffff;">Katalog Master Barang & Part Number (<?= count($barangs) ?> Item)</h3>
    <input type="text" id="tableSearchInput" class="form-control" placeholder="🔍 Cari P/N / nama / supplier..." style="max-width: 280px; padding: 8px 12px; font-size: 0.8rem;">
  </div>

  <div class="table-responsive">
    <table class="modern-table">
      <thead>
        <tr>
          <th>Kode & P/N</th>
          <th>Nama Barang</th>
          <th>Supplier Rekanan</th>
          <th>Kategori</th>
          <th>Satuan</th>
          <th style="text-align: right;">Stok Fisik</th>
          <th style="text-align: right;">Stok Min</th>
          <th>Lokasi Rak</th>
          <th style="text-align: center;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($barangs as $b): ?>
          <tr>
            <td>
              <strong style="color: #60a5fa;"><?= htmlspecialchars($b['kode_barang']) ?></strong>
              <?php if ($b['part_number']): ?>
                <div style="font-size: 0.75rem; color: #93c5fd; font-weight: 700;"><i class="bi bi-tag-fill"></i> <?= htmlspecialchars($b['part_number']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <strong style="color: #ffffff;"><?= htmlspecialchars($b['nama_barang']) ?></strong>
              <?php if ($b['spesifikasi']): ?>
                <div style="font-size: 0.7rem; color: var(--text-muted);"><?= htmlspecialchars($b['spesifikasi']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($b['nama_supplier']): ?>
                <span class="badge badge-success"><i class="bi bi-truck"></i> <?= htmlspecialchars($b['nama_supplier']) ?></span>
              <?php else: ?>
                <span style="font-size: 0.72rem; color: var(--text-dim);">-</span>
              <?php endif; ?>
            </td>
            <td><span class="badge badge-purple"><?= htmlspecialchars($b['nama_kategori']) ?></span></td>
            <td><?= htmlspecialchars($b['singkatan']) ?></td>
            <td style="text-align: right; font-weight: 800; font-size: 0.95rem; <?= $b['stok_saat_ini'] <= $b['stok_minimum'] ? 'color: #f87171;' : 'color: #34d399;' ?>">
              <?= formatStok($b['stok_saat_ini']) ?>
            </td>
            <td style="text-align: right; color: var(--text-muted);"><?= formatStok($b['stok_minimum']) ?></td>
            <td><span style="font-size: 0.8rem;"><?= htmlspecialchars($b['lokasi_rak'] ?: '-') ?></span></td>
            <td style="text-align: center;">
              <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete('barang.php?action=hapus&id=<?= $b['id'] ?>', '<?= htmlspecialchars($b['nama_barang']) ?>')">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
