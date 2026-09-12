<?php error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE); ?>
<?php
// config/database.php
// Konfigurasi Database & Helper Sistem Inventaris MaoneArt

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host     = '127.0.0.1'; // 127.0.0.1 (TCP/IP) kompatibel di Termux & Komputer Kantor (XAMPP/Laragon)
$port     = '3306';
$dbname   = 'db_inventaris';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=$dbname;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e2) {
        $db_error = $e->getMessage() . " (127.0.0.1) | " . $e2->getMessage() . " (localhost)";
    }
}

/**
 * Autentikasi Pengguna
 */
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        if (!headers_sent()) {
            header('Location: login.php');
            exit;
        } else {
            echo '<script>window.location.href="login.php";</script>';
            exit;
        }
    }
}

function getUserId() {
    return $_SESSION['user_id'] ?? 1;
}

function getUserRole() {
    return $_SESSION['user_role'] ?? 'admin';
}

function getUserName() {
    return $_SESSION['user_name'] ?? 'Petugas Gudang';
}

/**
 * Ambil Pengaturan dari app_settings
 */
function getSetting($key, $default = '') {
    global $pdo;
    if (!$pdo) return $default;
    try {
        $stmt = $pdo->prepare("SELECT key_value FROM app_settings WHERE key_name = ? LIMIT 1");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Flash Message untuk Alert Modal
 */
function setFlash($type, $title, $message) {
    $_SESSION['flash_message'] = [
        'type'    => $type, // 'success', 'danger', 'info'
        'title'   => $title,
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $msg;
    }
    return null;
}

/**
 * Generate No Transaksi Unik
 */
function generateNoTransaksi($tipe = 'IN') {
    global $pdo;
    $prefix = $tipe === 'IN' ? 'IN' : 'OUT';
    $datePart = date('ymd');
    $table = $tipe === 'IN' ? 'transaksi_masuk' : 'transaksi_keluar';
    $field = $tipe === 'IN' ? 'no_masuk' : 'no_keluar';

    try {
        $stmt = $pdo->prepare("SELECT $field FROM $table WHERE $field LIKE ? ORDER BY id DESC LIMIT 1");
        $stmt->execute(["TRX-$prefix-$datePart-%"]);
        $lastNo = $stmt->fetchColumn();

        if ($lastNo) {
            $lastSeq = (int) substr($lastNo, -4);
            $newSeq = str_pad($lastSeq + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newSeq = '0001';
        }
        return "TRX-$prefix-$datePart-$newSeq";
    } catch (Exception $e) {
        return "TRX-$prefix-$datePart-" . rand(1000, 9999);
    }
}

/**
 * Format Angka Stok (Hilangkan desimal jika bulat)
 */
function formatStok($val) {
    if ($val === null) return '0';
    $num = (float) $val;
    return floor($num) == $num ? number_format($num, 0, ',', '.') : number_format($num, 2, ',', '.');
}
