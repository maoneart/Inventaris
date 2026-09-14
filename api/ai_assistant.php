<?php
// api/ai_assistant.php - Backend AI Assistant "Tanya Si-nya" (Aman: Token Input Manual)
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);
$userMessage = trim($input['message'] ?? '');
$manualApiKey = trim($input['api_key'] ?? '');

if (empty($userMessage)) {
    echo json_encode(['success' => false, 'reply' => 'Pesan tidak boleh kosong.']);
    exit;
}

// 1. Kumpulkan Konteks Realtime dari Database
try {
    $stmtB = $pdo->query("
        SELECT b.kode_barang, b.nama_barang, b.stok_saat_ini, b.stok_minimum, b.lokasi_rak, 
               k.nama_kategori, s.singkatan
        FROM barang b
        LEFT JOIN kategori k ON b.id_kategori = k.id
        LEFT JOIN satuan s ON b.id_satuan = s.id
        ORDER BY b.nama_barang ASC
    ");
    $dataBarang = $stmtB->fetchAll();

    $barangKritis = array_filter($dataBarang, function($b) {
        return $b['stok_saat_ini'] <= $b['stok_minimum'];
    });

    $stmtM = $pdo->query("
        SELECT tm.no_masuk, tm.tanggal_masuk, s.nama_supplier, tm.total_item, tm.total_qty
        FROM transaksi_masuk tm
        JOIN supplier s ON tm.id_supplier = s.id
        ORDER BY tm.id DESC LIMIT 5
    ");
    $dataMasuk = $stmtM->fetchAll();

    $stmtK = $pdo->query("
        SELECT tk.no_keluar, tk.tanggal_keluar, p.nama_pic, p.departemen, tk.keperluan, tk.total_item, tk.total_qty
        FROM transaksi_keluar tk
        JOIN pic p ON tk.id_pic = p.id
        ORDER BY tk.id DESC LIMIT 5
    ");
    $dataKeluar = $stmtK->fetchAll();

    $suppliers = $pdo->query("SELECT nama_supplier, kontak_person, no_telp FROM supplier LIMIT 10")->fetchAll();
    $pics = $pdo->query("SELECT nama_pic, departemen, jabatan FROM pic LIMIT 10")->fetchAll();

    $contextSummary = [
        'tanggal_sekarang' => date('d-m-Y H:i'),
        'total_jenis_barang' => count($dataBarang),
        'total_barang_kritis' => count($barangKritis),
        'daftar_stok_barang' => array_map(function($b) {
            return "{$b['kode_barang']}: {$b['nama_barang']} [Stok: {$b['stok_saat_ini']} {$b['singkatan']}, Min: {$b['stok_minimum']}, Rak: {$b['lokasi_rak']}]";
        }, $dataBarang),
        'barang_menipis_perlu_restock' => array_map(function($b) {
            return "{$b['nama_barang']} (Sisa: {$b['stok_saat_ini']} {$b['singkatan']}, Min: {$b['stok_minimum']})";
        }, $barangKritis),
        'transaksi_masuk_terakhir' => $dataMasuk,
        'transaksi_keluar_terakhir' => $dataKeluar,
        'daftar_pic_peminta' => $pics,
        'daftar_supplier' => $suppliers
    ];

} catch (Exception $e) {
    echo json_encode(['success' => false, 'reply' => 'Gagal membaca database inventory: ' . $e->getMessage()]);
    exit;
}

// 2. System Prompt Cerdas
$systemPrompt = "Kamu adalah 'Si-nya', Asisten AI Logistik & Pergudangan MaoneArt (Inventory & Warehouse Intelligent Assistant).
Kamu bertugas membantu staf gudang, supervisor, dan manajemen untuk:
1. Menjawab pertanyaan seputar stok aktual barang secara realtime, lokasi rak, dan spesifikasi barang.
2. Memberikan analisa jika ada stok yang menipis/kritis dan merekomendasikan restock ke supplier terkait.
3. Menjelaskan arus barang: siapa PIC/teknisi yang mengambil barang/tools, untuk keperluan apa, serta riwayat kiriman supplier.
4. Membuat draf laporan atau ringkasan yang rapi (dalam bentuk tabel markdown yang estetik).
5. Jika pengguna meminta download/buatkan laporan PDF atau Excel, berikan analisa ringkasnya dan sediakan tautan langsung:
   - Export Stok Excel: [📥 Download Stok Excel](export.php?type=stok_excel)
   - Cetak Stok PDF: [📄 Cetak / Download Stok PDF](export.php?type=stok_pdf)
   - Export Laporan Arus Excel: [📊 Download Laporan Arus Excel](export.php?type=laporan_excel)
   - Cetak Laporan Arus PDF: [🖨️ Cetak Laporan Arus PDF](export.php?type=laporan_pdf)

ATURAN KOMUNIKASI:
- Bahasa: Bahasa Indonesia yang profesional, ringkas, padat, dan ramah khas MaoneArt.
- Selalu gunakan data aktual berikut yang diambil langsung dari database saat ini:
" . json_encode($contextSummary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// 3. Gunakan Token Gemini Manual dari Client atau Database
$geminiApiKey = !empty($manualApiKey) ? $manualApiKey : getSetting('gemini_api_key', '');

$reply = '';

if (!empty($geminiApiKey)) {
    $cleanToken = trim($geminiApiKey);
    if (stripos($cleanToken, 'bearer ') === 0) {
        $cleanToken = trim(substr($cleanToken, 7));
    }

    $models = ['gemini-1.5-flash', 'gemini-2.0-flash', 'gemini-2.5-flash'];
    $lastError = '';

    foreach ($models as $modelName) {
        $endpoints = [];
        // Format 1: Header x-goog-api-key + URL key
        $endpoints[] = [
            'url' => "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key=" . urlencode($cleanToken),
            'headers' => [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $cleanToken
            ]
        ];
        // Format 2: Authorization Bearer (untuk token model baru AQ... / OAuth)
        $endpoints[] = [
            'url' => "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent",
            'headers' => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $cleanToken
            ]
        ];

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $systemPrompt . "\n\nPertanyaan Pengguna: " . $userMessage]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 1500
            ]
        ];

        foreach ($endpoints as $ep) {
            $ch = curl_init($ep['url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $ep['headers']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 25);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $res) {
                $resJson = json_decode($res, true);
                $candidateText = $resJson['candidates'][0]['content']['parts'][0]['text'] ?? '';
                if (!empty($candidateText)) {
                    $reply = $candidateText;
                    break 2; // Berhasil! Keluar dari loop model & endpoint
                }
            } else if ($res) {
                $errJson = json_decode($res, true);
                if (!empty($errJson['error']['message'])) {
                    $lastError = $errJson['error']['message'];
                }
            }
        }
    }

    if (empty($reply) && !empty($lastError)) {
        $reply = "⚠️ Kendala API Gemini: $lastError. Pastikan token/API Key yang Anda masukkan masih aktif.";
    }
}

// Fallback jika belum input API Key Gemini
if (empty($reply)) {
    $lowerMsg = strtolower($userMessage);
    if (empty($geminiApiKey)) {
        $reply = "⚠️ **Token Gemini Belum Dimasukkan!**\n\n"
               . "Demi keamanan data Anda agar token tidak bocor di GitHub, silakan klik tombol **'🔑 Atur Token Gemini'** di pojok kanan atas untuk memasukkan token API Gemini Anda secara manual.\n\n"
               . "*Token akan tersimpan aman di browser/HP Anda sendiri, tidak disimpan di file kodingan.*";
    } elseif (strpos($lowerMsg, 'kritis') !== false || strpos($lowerMsg, 'menipis') !== false || strpos($lowerMsg, 'habis') !== false) {
        $count = count($barangKritis);
        $reply = "### ⚠️ Laporan Stok Menipis & Kritis\nSaat ini terdapat **{$count} barang** yang berada pada atau di bawah batas minimum:\n\n";
        foreach ($barangKritis as $bk) {
            $reply .= "- **{$bk['nama_barang']}**: Sisa **{$bk['stok_saat_ini']} {$bk['singkatan']}** (Batas Min: {$bk['stok_minimum']}) • *Lokasi: {$bk['lokasi_rak']}*\n";
        }
        $reply .= "\n[📥 Download Laporan Stok Excel](export.php?type=stok_excel) | [📄 Cetak PDF](export.php?type=stok_pdf)";
    } elseif (strpos($lowerMsg, 'excel') !== false || strpos($lowerMsg, 'pdf') !== false || strpos($lowerMsg, 'laporan') !== false) {
        $reply = "### 📊 Layanan Ekspor Laporan Gudang Siap Cetak\n\n"
               . "1. **Laporan Aktual Stok Gudang Realtime**:\n"
               . "   - [📥 Download Format Excel (.xls)](export.php?type=stok_excel)\n"
               . "   - [📄 Cetak / Simpan PDF Siap Tanda Tangan](export.php?type=stok_pdf)\n\n"
               . "2. **Laporan Rekapitulasi Arus Masuk/Keluar**:\n"
               . "   - [📊 Download Rekap Mutasi Excel](export.php?type=laporan_excel)\n"
               . "   - [🖨️ Cetak Rekap Mutasi PDF](export.php?type=laporan_pdf)\n\n";
    }
}

echo json_encode(['success' => true, 'reply' => $reply]);
