<?php
// pic.php - Master Data PIC / Karyawan Pengambil Barang
$pageTitle = "Master Data PIC";
require_once __DIR__ . '/config/database.php';

// Tambah PIC
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_pic') {
    $nip_nik = trim($_POST['nip_nik'] ?? '');
    $nama_pic = trim($_POST['nama_pic'] ?? '');
    $departemen = trim($_POST['departemen'] ?? '');
    $jabatan = trim($_POST['jabatan'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');

    if (empty($nama_pic) || empty($departemen)) {
        setFlash('danger', 'Validasi Gagal', 'Nama PIC dan Departemen wajib diisi.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO pic (nip_nik, nama_pic, departemen, jabatan, no_hp) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nip_nik, $nama_pic, $departemen, $jabatan, $no_hp]);
            setFlash('success', 'PIC Ditambahkan', "Data PIC <strong>$nama_pic</strong> berhasil didaftarkan.");
        } catch (Exception $e) {
            setFlash('danger', 'Gagal Tambah PIC', $e->getMessage());
        }
    }
    header('Location: pic.php');
    exit;
}

// Hapus PIC
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM pic WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Data Dihapus', 'Data PIC berhasil dihapus.');
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1451) {
            setFlash('danger', 'Tidak Bisa Dihapus', 'PIC ini memiliki riwayat pengambilan barang di data keluar.');
        } else {
            setFlash('danger', 'Gagal Hapus', $e->getMessage());
        }
    }
    header('Location: pic.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';

$pics = $pdo->query("
    SELECT p.*, COUNT(tk.id) as total_pengambilan
    FROM pic p
    LEFT JOIN transaksi_keluar tk ON p.id = tk.id_pic
    GROUP BY p.id
    ORDER BY p.departemen ASC, p.nama_pic ASC
")->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
  <div>
    <h2 style="font-size: 1.25rem; font-weight: 800; color: #ffffff; display: flex; align-items: center; gap: 8px;">
      <i class="bi bi-people-fill text-primary"></i> Master Data PIC / Peminta Barang
    </h2>
    <p style="font-size: 0.78rem; color: var(--text-muted);">Daftar teknisi, operator, dan penanggung jawab yang berwenang mengambil tools/material</p>
  </div>
  <a href="index.php" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Dashboard</a>
</div>

<div class="glass-card">
  <h3 style="font-size: 1rem; font-weight: 700; color: #60a5fa; margin-bottom: 14px; display: flex; align-items: center; gap: 6px;">
    <i class="bi bi-person-plus-fill"></i> Tambah PIC Baru
  </h3>
  <form action="pic.php" method="POST">
    <input type="hidden" name="action" value="tambah_pic">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px;">
      <div class="form-group">
        <label class="form-label">NIP / NIK / ID Karyawan</label>
        <input type="text" name="nip_nik" class="form-control" placeholder="Contoh: PIC-106">
      </div>
      <div class="form-group">
        <label class="form-label">Nama Lengkap PIC <span style="color: #ef4444;">*</span></label>
        <input type="text" name="nama_pic" class="form-control" placeholder="Nama karyawan / teknisi" required>
      </div>
      <div class="form-group">
        <label class="form-label">Departemen / Divisi <span style="color: #ef4444;">*</span></label>
        <input type="text" name="departemen" class="form-control" placeholder="Contoh: Maintenance, Produksi, QA" required>
      </div>
      <div class="form-group">
        <label class="form-label">Jabatan</label>
        <input type="text" name="jabatan" class="form-control" placeholder="Contoh: Teknisi, Leader, Spv">
      </div>
      <div class="form-group">
        <label class="form-label">No. HP / WhatsApp</label>
        <input type="text" name="no_hp" class="form-control" placeholder="0812-xxxx-xxxx">
      </div>
    </div>
    <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-save-fill"></i> Simpan PIC
      </button>
    </div>
  </form>
</div>

<div class="glass-card">
  <h3 style="font-size: 1rem; font-weight: 700; color: #ffffff; margin-bottom: 14px;">Daftar PIC Terdaftar</h3>
  <div class="table-responsive">
    <table class="modern-table">
      <thead>
        <tr>
          <th>ID / NIP</th>
          <th>Nama PIC</th>
          <th>Departemen</th>
          <th>Jabatan</th>
          <th>No. Kontak</th>
          <th style="text-align: center;">Total Ambil</th>
          <th style="text-align: center;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pics as $p): ?>
          <tr>
            <td><strong style="color: #60a5fa;"><?= htmlspecialchars($p['nip_nik'] ?: '-') ?></strong></td>
            <td><strong style="color: #ffffff;"><?= htmlspecialchars($p['nama_pic']) ?></strong></td>
            <td><span class="badge badge-purple"><?= htmlspecialchars($p['departemen']) ?></span></td>
            <td><?= htmlspecialchars($p['jabatan'] ?: '-') ?></td>
            <td><?= htmlspecialchars($p['no_hp'] ?: '-') ?></td>
            <td style="text-align: center;"><span class="badge badge-danger"><?= $p['total_pengambilan'] ?> Transaksi</span></td>
            <td style="text-align: center;">
              <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete('pic.php?action=hapus&id=<?= $p['id'] ?>', '<?= htmlspecialchars($p['nama_pic']) ?>')">
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
