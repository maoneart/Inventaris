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

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
  <div>
    <h2 style="font-size: 1.25rem; font-weight: 800; color: #ffffff; display: flex; align-items: center; gap: 8px;">
      <i class="bi bi-truck text-success"></i> Master Data Supplier (Asal Pemasukan)
    </h2>
    <p style="font-size: 0.78rem; color: var(--text-muted);">Penyedia material, tools, sparepart, dan perlengkapan gudang</p>
  </div>
  <a href="index.php" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Dashboard</a>
</div>

<div class="glass-card">
  <h3 style="font-size: 1rem; font-weight: 700; color: #34d399; margin-bottom: 14px; display: flex; align-items: center; gap: 6px;">
    <i class="bi bi-plus-circle-fill"></i> Tambah Supplier Baru
  </h3>
  <form action="supplier.php" method="POST">
    <input type="hidden" name="action" value="tambah_supplier">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
      <div class="form-group">
        <label class="form-label">Kode Supplier</label>
        <input type="text" name="kode_supplier" class="form-control" value="<?= htmlspecialchars($saranKodeSup) ?>">
      </div>
      <div class="form-group" style="grid-column: span 2;">
        <label class="form-label">Nama Perusahaan / Supplier <span style="color: #ef4444;">*</span></label>
        <input type="text" name="nama_supplier" class="form-control" placeholder="Contoh: PT Sumber Baja Perkasa" required>
      </div>
      <div class="form-group">
        <label class="form-label">Kontak Person (PIC Supplier)</label>
        <input type="text" name="kontak_person" class="form-control" placeholder="Nama sales / marketing">
      </div>
      <div class="form-group">
        <label class="form-label">No. Telepon / WhatsApp</label>
        <input type="text" name="no_telp" class="form-control" placeholder="0812-xxxx-xxxx">
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" placeholder="sales@supplier.com">
      </div>
      <div class="form-group" style="grid-column: span 3;">
        <label class="form-label">Alamat / Lokasi Pergudangan</label>
        <input type="text" name="alamat" class="form-control" placeholder="Alamat kantor / workshop supplier...">
      </div>
    </div>
    <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
      <button type="submit" class="btn btn-success">
        <i class="bi bi-save-fill"></i> Simpan Supplier
      </button>
    </div>
  </form>
</div>

<div class="glass-card">
  <h3 style="font-size: 1rem; font-weight: 700; color: #ffffff; margin-bottom: 14px;">Daftar Rekanan Supplier</h3>
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
        <?php foreach ($suppliers as $s): ?>
          <tr>
            <td><strong style="color: #60a5fa;"><?= htmlspecialchars($s['kode_supplier'] ?: '-') ?></strong></td>
            <td><strong style="color: #ffffff;"><?= htmlspecialchars($s['nama_supplier']) ?></strong></td>
            <td><?= htmlspecialchars($s['kontak_person'] ?: '-') ?></td>
            <td><?= htmlspecialchars($s['no_telp'] ?: '-') ?></td>
            <td><span style="font-size: 0.78rem; color: var(--text-muted);"><?= htmlspecialchars($s['alamat'] ?: '-') ?></span></td>
            <td style="text-align: center;"><span class="badge badge-info"><?= $s['total_pengiriman'] ?> Kali Pasok</span></td>
            <td style="text-align: center;">
              <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete('supplier.php?action=hapus&id=<?= $s['id'] ?>', '<?= htmlspecialchars($s['nama_supplier']) ?>')">
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
