<?php
if(!defined('DB_SERVER')) { header("location: ../login.php"); exit; }

// --- Inisialisasi Variabel ---
$mahasiswa_nrp = $_SESSION['id'];
$action = $_GET['action'] ?? 'list';
$peminjaman_id_review = $_GET['id'] ?? null;
$flash_message = $_SESSION['flash_message'] ?? null;
$form_error = "";

// Hapus flash message dari session setelah diambil
if (isset($_SESSION['flash_message'])) {
    unset($_SESSION['flash_message']);
}


// --- Blok untuk Menangani Semua Aksi dari Form (POST Request) ---

// Handle form submission untuk review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $peminjaman_id = $_POST['peminjaman_id'];
    $komentar = trim($_POST['komentar']);
    $saran = trim($_POST['saran_kritik']);
    
    // Pembuatan ID review yang lebih andal untuk mencegah duplikat
    $last_id_result = $mysqli->query("SELECT MAX(CAST(SUBSTRING(ID_review, 4) AS UNSIGNED)) as max_id FROM Review");
    $last_id_row = $last_id_result->fetch_assoc();
    $next_id_num = ($last_id_row['max_id'] ?? 0) + 1;
    $review_id = "REV" . str_pad($next_id_num, 3, '0', STR_PAD_LEFT);

    $mysqli->begin_transaction();
    try {
        // 1. Insert ke tabel Review
        $sql_review = "INSERT INTO Review (ID_review, komentar, saran_kritik, waktu) VALUES (?, ?, ?, NOW())";
        $stmt_review = $mysqli->prepare($sql_review);
        $stmt_review->bind_param("sss", $review_id, $komentar, $saran);
        $stmt_review->execute();
        $stmt_review->close();

        // 2. Update tabel Peminjaman dengan ID_review yang baru
        $sql_peminjaman = "UPDATE Peminjaman SET ID_review = ? WHERE ID_peminjaman = ?";
        $stmt_peminjaman = $mysqli->prepare($sql_peminjaman);
        $stmt_peminjaman->bind_param("ss", $review_id, $peminjaman_id);
        $stmt_peminjaman->execute();
        $stmt_peminjaman->close();

        $mysqli->commit();
        $_SESSION['flash_message'] = "Terima kasih atas review Anda!";
    } catch (mysqli_sql_exception $e) {
        $mysqli->rollback();
        // Tampilkan pesan error jika gagal
        $form_error = "Gagal menyimpan review. Error: " . $e->getMessage();
    }
    
    if (empty($form_error)) {
        header("location: dashboard.php?page=riwayat");
        exit;
    }
}

// Handle aksi saat mahasiswa mengajukan pengembalian
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajukan_kembali'])) {
    $peminjaman_id = $_POST['peminjaman_id'];
    
    // Query untuk mengubah status menjadi 'Diajukan untuk Kembali'
    $sql_ajukan = "UPDATE Peminjaman SET status = 'Diajukan untuk Kembali' WHERE ID_peminjaman = ? AND NRP = ?";
    if ($stmt_ajukan = $mysqli->prepare($sql_ajukan)) {
        $stmt_ajukan->bind_param("ss", $peminjaman_id, $mahasiswa_nrp);
        if ($stmt_ajukan->execute()) {
            $_SESSION['flash_message'] = "Pengajuan pengembalian berhasil. Silakan kembalikan alat ke staf lab.";
        }
        $stmt_ajukan->close();
        header("location: dashboard.php?page=riwayat");
        exit;
    }
}


// --- Blok untuk Mengambil Data dari Database ---

// Ambil data semua peminjaman milik mahasiswa yang sedang login
$sql = "SELECT p.ID_peminjaman, p.tanggal_pinjam, p.status, p.ID_review, a.nama_alat 
        FROM Peminjaman p 
        LEFT JOIN Peminjaman_Alat pa ON p.ID_peminjaman = pa.ID_peminjaman
        LEFT JOIN Alat a ON pa.ID_alat = a.ID_alat
        WHERE p.NRP = ? 
        ORDER BY p.tanggal_pinjam DESC";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $mahasiswa_nrp);
$stmt->execute();
$result = $stmt->get_result();
$peminjaman_list = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>

<div class="bg-white p-6 rounded-lg shadow">
    <?php  
        if (!empty($flash_message)) {
            echo '<div class="alert alert-success">' . $flash_message . '</div>';
        }
        if (!empty($form_error)) {
            echo '<div class="alert alert-danger">' . $form_error . '</div>';
        }
    ?>

    <?php if ($action == 'review' && $peminjaman_id_review): ?>
    
        <h3 class="text-xl font-semibold mb-4">Beri Review untuk Peminjaman #<?php echo htmlspecialchars($peminjaman_id_review); ?></h3>
        <form action="dashboard.php?page=riwayat" method="POST" class="space-y-4 max-w-lg">
            <input type="hidden" name="peminjaman_id" value="<?php echo htmlspecialchars($peminjaman_id_review); ?>">
            <div>
                <label for="komentar" class="block font-medium text-gray-700">Komentar</label>
                <textarea name="komentar" id="komentar" rows="3" class="form-input mt-1" placeholder="Bagaimana pengalaman Anda menggunakan alat ini?"></textarea>
            </div>
            <div>
                <label for="saran_kritik" class="block font-medium text-gray-700">Saran & Kritik</label>
                <textarea name="saran_kritik" id="saran_kritik" rows="3" class="form-input mt-1" placeholder="Adakah yang bisa kami tingkatkan dari proses peminjaman?"></textarea>
            </div>
            <div class="flex items-center space-x-4 pt-2">
                <button type="submit" name="submit_review" class="bg-indigo-600 text-white px-5 py-2 rounded-md hover:bg-indigo-700">Kirim Review</button>
                <a href="dashboard.php?page=riwayat" class="bg-gray-200 text-gray-800 px-5 py-2 rounded-md hover:bg-gray-300">Batal</a>
            </div>
        </form>
    
    <?php else: ?>
    
        <h3 class="text-xl font-semibold mb-4">Riwayat Peminjaman Saya</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Alat Dipinjam</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Tgl Pinjam</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($peminjaman_list) > 0): ?>
                        <?php foreach ($peminjaman_list as $p): ?>
                            <tr>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($p['nama_alat'] ?? 'N/A'); ?></td>
                                <td class="px-6 py-4"><?php echo date('d M Y', strtotime($p['tanggal_pinjam'])); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($p['status']); ?></td>
                                <td class="px-6 py-4">
                                    <?php if ($p['status'] == 'Dipinjam'): ?>
                                        <form method="POST" action="dashboard.php?page=riwayat" class="inline">
                                            <input type="hidden" name="peminjaman_id" value="<?php echo $p['ID_peminjaman']; ?>">
                                            <button type="submit" name="ajukan_kembali" class="text-blue-600 hover:underline">Ajukan Pengembalian</button>
                                        </form>
                                    <?php elseif ($p['status'] == 'Diajukan untuk Kembali'): ?>
                                        <span class="text-gray-500">Menunggu Konfirmasi Staf</span>
                                    <?php elseif ($p['status'] == 'Dikembalikan' && is_null($p['ID_review'])): ?>
                                        <a href="dashboard.php?page=riwayat&action=review&id=<?php echo $p['ID_peminjaman']; ?>" class="text-indigo-600 hover:underline">Beri Review</a>
                                    <?php elseif (!is_null($p['ID_review'])): ?>
                                        <span class="text-gray-500">Sudah direview</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center px-6 py-4">Anda belum pernah melakukan peminjaman.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    
    <?php endif; ?>
</div>