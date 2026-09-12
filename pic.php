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

<!-- Header: Cuma Tombol Back Saja -->
<div class="ios-nav-header-simple">
  <a href="index.php" class="ios-back-btn">
    <i class="bi bi-chevron-left"></i> Kembali
  </a>
</div>

<!-- Judul di Dalam Konten -->
<div class="page-title-box">
  <h1 class="page-title">Data PIC</h1>
  <p class="page-subtitle">Teknisi & penanggung jawab pengambil barang</p>
</div>

<form action="pic.php" method="POST" id="formPic">
  <input type="hidden" name="action" value="tambah_pic">

  <!-- Group 1: Identitas PIC -->
  <div class="ios-form-card">
    <div class="ios-group-title">
      <i class="bi bi-person-badge"></i> PROFIL & IDENTITAS KARYAWAN
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
      <div>
        <label class="ios-label">NIP / NIK / ID Karyawan</label>
        <input type="text" name="nip_nik" class="ios-input" placeholder="Contoh: PIC-106 / 2024001">
      </div>

      <div style="grid-column: span 2;">
        <label class="ios-label">Nama Lengkap PIC <span style="color: #ef4444;">*</span></label>
        <input type="text" name="nama_pic" class="ios-input" placeholder="Nama karyawan / teknisi lapangan" required>
      </div>
    </div>
  </div>

  <!-- Group 2: Departemen & Kontak -->
  <div class="ios-form-card">
    <div class="ios-group-title">
      <i class="bi bi-briefcase"></i> DIVISI & KONTAK
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px;">
      <div>
        <label class="ios-label">Departemen / Divisi <span style="color: #ef4444;">*</span></label>
        <input type="text" name="departemen" class="ios-input" placeholder="Contoh: Maintenance, Produksi, QA" required>
      </div>

      <div>
        <label class="ios-label">Jabatan / Role</label>
        <input type="text" name="jabatan" class="ios-input" placeholder="Contoh: Teknisi, Leader, Spv">
      </div>

      <div>
        <label class="ios-label">No. HP / WhatsApp</label>
        <input type="text" name="no_hp" class="ios-input" placeholder="Contoh: 0812-xxxx-xxxx">
      </div>
    </div>

    <div style="margin-top: 20px;">
      <button type="submit" class="ios-btn-primary ios-btn-blue">
        <i class="bi bi-check-circle-fill"></i> Daftarkan PIC Baru
      </button>
    </div>
  </div>
</form>

<!-- Daftar PIC Terdaftar -->
<div class="ios-form-card" style="padding: 18px;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; gap: 12px; flex-wrap: wrap;">
    <div class="ios-group-title" style="margin-bottom: 0;">
      <i class="bi bi-people-fill"></i> DAFTAR PIC TERDAFTAR (<?= count($pics) ?> ORANG)
    </div>
    <input type="text" id="tableSearchInput" class="ios-input" placeholder="🔍 Cari nama / NIP / divisi..." style="max-width: 280px; padding: 8px 12px; font-size: 0.8rem;">
  </div>

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
        <?php if (count($pics) > 0): ?>
          <?php foreach ($pics as $p): ?>
            <tr>
              <td><strong style="color: #60a5fa;"><?= htmlspecialchars($p['nip_nik'] ?: '-') ?></strong></td>
              <td><strong style="color: #ffffff;"><?= htmlspecialchars($p['nama_pic']) ?></strong></td>
              <td><span class="badge badge-purple"><?= htmlspecialchars($p['departemen']) ?></span></td>
              <td><?= htmlspecialchars($p['jabatan'] ?: '-') ?></td>
              <td>
                <?php if ($p['no_hp']): ?>
                  <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $p['no_hp']) ?>" target="_blank" style="color: #34d399; text-decoration: none;">
                    <i class="bi bi-whatsapp"></i> <?= htmlspecialchars($p['no_hp']) ?>
                  </a>
                <?php else: ?>
                  <span style="color: var(--text-dim);">-</span>
                <?php endif; ?>
              </td>
              <td style="text-align: center;"><span class="badge badge-danger"><?= $p['total_pengambilan'] ?> Transaksi</span></td>
              <td style="text-align: center;">
                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete('pic.php?action=hapus&id=<?= $p['id'] ?>', '<?= htmlspecialchars($p['nama_pic']) ?>')">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="7" style="text-align: center; color: var(--text-dim); padding: 18px;">Belum ada PIC terdaftar.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
