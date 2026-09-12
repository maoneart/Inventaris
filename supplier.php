<?php
// supplier.php - Master Data Supplier (Asal Barang Masuk)
$pageTitle = "Master Data Supplier";
require_once __DIR__ . '/config/database.php';

// Tambah Supplier Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_supplier') {
    $kode_supplier = trim($_POST['kode_supplier'] ?? '');
    $nama_supplier = trim($_POST['nama_supplier'] ?? '');
    $kontak_person = trim($_POST['kontak_person'] ?? '');
    $no_telp = trim($_POST['no_telp'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    if (empty($nama_supplier)) {
        setFlash('danger', 'Validasi Gagal', 'Nama Supplier wajib diisi.');
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO supplier (kode_supplier, nama_supplier, kontak_person, no_telp, email, alamat)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$kode_supplier, $nama_supplier, $kontak_person, $no_telp, $email, $alamat]);
            setFlash('success', 'Supplier Ditambahkan', "Supplier <strong>$nama_supplier</strong> berhasil disimpan.");
        } catch (PDOException $e) {
            setFlash('danger', 'Gagal Tambah Supplier', $e->getMessage());
        }
    }
    header('Location: supplier.php');
    exit;
}

// Hapus Supplier
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM supplier WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Data Dihapus', 'Data supplier berhasil dihapus.');
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1451) {
            setFlash('danger', 'Tidak Bisa Dihapus', 'Supplier ini sudah memiliki riwayat pengiriman barang masuk.');
        } else {
            setFlash('danger', 'Gagal Hapus', $e->getMessage());
        }
    }
    header('Location: supplier.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';

$suppliers = $pdo->query("
    SELECT s.*, COUNT(tm.id) as total_pengiriman
    FROM supplier s
    LEFT JOIN transaksi_masuk tm ON s.id = tm.id_supplier
    GROUP BY s.id
    ORDER BY s.nama_supplier ASC
")->fetchAll();

$nextCount = count($suppliers) + 1;
$saranKodeSup = 'SUP-' . str_pad($nextCount, 3, '0', STR_PAD_LEFT);
?>

<!-- iOS Minimalist Header Ala iPhone (Exact Screenshot) -->
<div class="ios-top-bar">
  <a href="index.php" class="ios-circle-back" title="Kembali ke Dashboard">
    <i class="bi bi-chevron-left"></i>
  </a>
  <h1 class="ios-bar-title">Data Supplier</h1>
  <div class="ios-bar-action"></div>
</div>

<form action="supplier.php" method="POST" id="formSupplier">
  <input type="hidden" name="action" value="tambah_supplier">

  <!-- Group 1: Profil Supplier -->
  <div class="ios-form-card">
    <div class="ios-group-title">
      <i class="bi bi-building"></i> PROFIL & IDENTITAS VENDOR
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
      <div>
        <label class="ios-label">Kode Supplier</label>
        <input type="text" name="kode_supplier" class="ios-input" value="<?= htmlspecialchars($saranKodeSup) ?>" style="color: #059669; font-weight: 700;">
      </div>

      <div style="grid-column: span 2;">
        <label class="ios-label">Nama Perusahaan / Supplier <span style="color: #ef4444;">*</span></label>
        <input type="text" name="nama_supplier" class="ios-input" placeholder="Contoh: PT Sumber Baja Perkasa" required>
      </div>
    </div>
  </div>

  <!-- Group 2: Kontak & Alamat -->
  <div class="ios-form-card">
    <div class="ios-group-title">
      <i class="bi bi-telephone"></i> KONTAK & LOKASI
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px;">
      <div>
        <label class="ios-label">Kontak Person (Sales / PIC)</label>
        <input type="text" name="kontak_person" class="ios-input" placeholder="Contoh: Bpk. Hendra">
      </div>

      <div>
        <label class="ios-label">No. Telepon / WhatsApp</label>
        <input type="text" name="no_telp" class="ios-input" placeholder="Contoh: 0812-3456-7890">
      </div>

      <div>
        <label class="ios-label">Email Kantor</label>
        <input type="email" name="email" class="ios-input" placeholder="sales@supplier.com">
      </div>
    </div>

    <div style="margin-top: 14px;">
      <label class="ios-label">Alamat Lengkap / Gudang Vendor</label>
      <input type="text" name="alamat" class="ios-input" placeholder="Jalan Industri No. 45, Kawasan MM2100, Cikarang">
    </div>

    <div style="margin-top: 20px;">
      <button type="submit" class="ios-btn-primary ios-btn-green">
        <i class="bi bi-check-circle-fill"></i> Simpan Supplier Rekanan
      </button>
    </div>
  </div>
</form>

<!-- Daftar Rekanan Supplier -->
<div class="ios-form-card" style="padding: 18px;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; gap: 12px; flex-wrap: wrap;">
    <div class="ios-group-title" style="margin-bottom: 0;">
      <i class="bi bi-truck"></i> DAFTAR REKANAN SUPPLIER (<?= count($suppliers) ?> VENDOR)
    </div>
    <input type="text" id="tableSearchInput" class="ios-input" placeholder="🔍 Cari supplier / PIC / telepon..." style="max-width: 280px; padding: 8px 12px; font-size: 0.8rem;">
  </div>

  <div class="table-responsive">
    <table class="modern-table">
      <thead>
        <tr>
          <th>Kode</th>
          <th>Nama Supplier</th>
          <th>Kontak Person</th>
          <th>No. Telp / WA</th>
          <th>Alamat</th>
          <th style="text-align: center;">Total Transaksi</th>
          <th style="text-align: center;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($suppliers) > 0): ?>
          <?php foreach ($suppliers as $s): ?>
            <tr>
              <td><strong class="text-primary"><?= htmlspecialchars($s['kode_supplier'] ?: '-') ?></strong></td>
              <td><strong style="color: var(--text-main);"><?= htmlspecialchars($s['nama_supplier']) ?></strong></td>
              <td><?= htmlspecialchars($s['kontak_person'] ?: '-') ?></td>
              <td>
                <?php if ($s['no_telp']): ?>
                  <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $s['no_telp']) ?>" target="_blank" style="color: #059669; text-decoration: none; font-weight: 700;">
                    <i class="bi bi-whatsapp"></i> <?= htmlspecialchars($s['no_telp']) ?>
                  </a>
                <?php else: ?>
                  <span style="color: var(--text-dim);">-</span>
                <?php endif; ?>
              </td>
              <td><span style="font-size: 0.78rem; color: var(--text-muted);"><?= htmlspecialchars($s['alamat'] ?: '-') ?></span></td>
              <td style="text-align: center;"><span class="badge badge-info"><?= $s['total_pengiriman'] ?> Kali Pasok</span></td>
              <td style="text-align: center;">
                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete('supplier.php?action=hapus&id=<?= $s['id'] ?>', '<?= htmlspecialchars($s['nama_supplier']) ?>')">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="7" style="text-align: center; color: var(--text-dim); padding: 18px;">Belum ada supplier terdaftar.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
