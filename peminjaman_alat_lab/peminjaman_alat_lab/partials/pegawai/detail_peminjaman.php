<?php
if(!defined('DB_SERVER')) { header("location: ../login.php"); exit; }

$peminjaman_id = $_GET['id'] ?? null;
if (!$peminjaman_id) {
    echo "<div class='alert alert-danger'>ID Peminjaman tidak valid.</div>";
    exit;
}

$message = "";

// Handle Aksi Setujui / Tolak / Kembalikan dari form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $pegawai_id = $_SESSION['id'];
    $alat_id = $_POST['alat_id'] ?? null;

    if ($_POST['aksi'] === 'setujui') {
        $tanggal_kembali = date('Y-m-d H:i:s', strtotime('+7 days'));
        $sql_update = "UPDATE Peminjaman SET status = 'Dipinjam', ID_pegawai = ?, tanggal_kembali = ? WHERE ID_peminjaman = ?";
        if ($stmt = $mysqli->prepare($sql_update)) {
            $stmt->bind_param("sss", $pegawai_id, $tanggal_kembali, $peminjaman_id);
            if ($stmt->execute()) {
                $_SESSION['flash_message'] = "Peminjaman berhasil disetujui.";
                header("location: dashboard.php?page=peminjaman");
                exit;
            }
        }
    } 
    elseif ($_POST['aksi'] === 'tolak') {
        $mysqli->begin_transaction();
        try {
            $sql_update = "UPDATE Peminjaman SET status = 'Ditolak', ID_pegawai = ? WHERE ID_peminjaman = ?";
            $stmt = $mysqli->prepare($sql_update);
            $stmt->bind_param("ss", $pegawai_id, $peminjaman_id);
            $stmt->execute();
            $stmt->close();

            $sql_stok = "UPDATE Alat SET stok_alat = stok_alat + 1 WHERE ID_alat = ?";
            $stmt_stok = $mysqli->prepare($sql_stok);
            $stmt_stok->bind_param("s", $alat_id);
            $stmt_stok->execute();
            $stmt_stok->close();

            $mysqli->commit();
            $_SESSION['flash_message'] = "Peminjaman telah ditolak.";
            header("location: dashboard.php?page=peminjaman");
            exit;
        } catch (mysqli_sql_exception $exception) {
            $mysqli->rollback();
            $message = "<div class='alert alert-danger'>Gagal menolak peminjaman.</div>";
        }
    }
    elseif ($_POST['aksi'] === 'kembalikan') {
        $mysqli->begin_transaction();
        try {
            $tanggal_kembali_aktual = date('Y-m-d H:i:s');
            $sql_update = "UPDATE Peminjaman SET status = 'Dikembalikan', tanggal_kembali = ? WHERE ID_peminjaman = ?";
            $stmt = $mysqli->prepare($sql_update);
            $stmt->bind_param("ss", $tanggal_kembali_aktual, $peminjaman_id);
            $stmt->execute();
            $stmt->close();

            $sql_stok = "UPDATE Alat SET stok_alat = stok_alat + 1 WHERE ID_alat = ?";
            $stmt_stok = $mysqli->prepare($sql_stok);
            $stmt_stok->bind_param("s", $alat_id);
            $stmt_stok->execute();
            $stmt_stok->close();
            
            $mysqli->commit();
            $_SESSION['flash_message'] = "Alat telah ditandai sebagai 'Dikembalikan' dan stok telah diperbarui.";
            header("location: dashboard.php?page=peminjaman");
            exit;
        } catch (mysqli_sql_exception $exception) {
            $mysqli->rollback();
            $message = "<div class='alert alert-danger'>Gagal memproses pengembalian. Error: " . $exception->getMessage() . "</div>";
        }
    }
}

// Query untuk mengambil detail peminjaman untuk ditampilkan di halaman
$sql = "SELECT p.ID_peminjaman, p.tanggal_pinjam, p.status, 
               m.Nama as nama_mahasiswa, m.NRP, 
               a.ID_alat, a.nama_alat
        FROM Peminjaman p
        LEFT JOIN Mahasiswa m ON p.NRP = m.NRP
        LEFT JOIN Peminjaman_Alat pa ON p.ID_peminjaman = pa.ID_peminjaman
        LEFT JOIN Alat a ON pa.ID_alat = a.ID_alat
        WHERE p.ID_peminjaman = ?";

$peminjaman = null;
if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("s", $peminjaman_id);
    if($stmt->execute()){
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $peminjaman = $result->fetch_assoc();
        }
    }
    $stmt->close();
}

// Jika setelah query data masih tidak ditemukan, hentikan script.
if (!$peminjaman) {
    echo "<div class='bg-white p-6 rounded-lg shadow'><div class='alert alert-danger'>Detail Peminjaman dengan ID #".htmlspecialchars($peminjaman_id)." tidak ditemukan.</div></div>";
    exit;
}

?>
<div class="bg-white p-6 rounded-lg shadow">
    <h3 class="text-xl font-semibold mb-4">Detail Peminjaman #<?php echo htmlspecialchars($peminjaman['ID_peminjaman']); ?></h3>
    <?php echo $message; ?>

    <div class="space-y-4">
        <div>
            <h4 class="font-medium text-gray-500">Nama Peminjam</h4>
            <p class="text-lg"><?php echo htmlspecialchars($peminjaman['nama_mahasiswa'] ?? 'Data tidak ditemukan'); ?> (<?php echo htmlspecialchars($peminjaman['NRP'] ?? ''); ?>)</p>
        </div>
        <div>
            <h4 class="font-medium text-gray-500">Tanggal Pinjam</h4>
            <p class="text-lg"><?php echo date('d M Y H:i', strtotime($peminjaman['tanggal_pinjam'])); ?></p>
        </div>
        <div>
            <h4 class="font-medium text-gray-500">Alat yang Dipinjam</h4>
            <p class="text-lg"><?php echo htmlspecialchars($peminjaman['nama_alat'] ?? 'Data alat tidak ditemukan'); ?></p>
        </div>
        <div>
            <h4 class="font-medium text-gray-500">Status</h4>
            <p class="text-lg font-semibold">
                <?php echo htmlspecialchars($peminjaman['status']); ?>
            </p>
        </div>
    </div>

    <div class="mt-6 border-t pt-6 flex items-center space-x-4">
        <?php if ($peminjaman['status'] == 'Menunggu Persetujuan'): ?>
            <form method="POST" action="">
                <input type="hidden" name="alat_id" value="<?php echo htmlspecialchars($peminjaman['ID_alat']); ?>">
                <input type="hidden" name="aksi" value="setujui">
                <button type="submit" class="bg-green-500 text-white px-5 py-2 rounded-md hover:bg-green-600">Setujui Peminjaman</button>
            </form>
            <form method="POST" action="">
                <input type="hidden" name="alat_id" value="<?php echo htmlspecialchars($peminjaman['ID_alat']); ?>">
                <input type="hidden" name="aksi" value="tolak">
                <button type="submit" class="bg-red-500 text-white px-5 py-2 rounded-md hover:bg-red-600" onclick="return confirm('Anda yakin ingin menolak peminjaman ini? Stok alat akan dikembalikan.');">Tolak</button>
            </form>
        <?php endif; ?>

        <?php if ($peminjaman['status'] == 'Diajukan untuk Kembali'): ?>
            <form method="POST" action="">
                <input type="hidden" name="alat_id" value="<?php echo htmlspecialchars($peminjaman['ID_alat']); ?>">
                <input type="hidden" name="aksi" value="kembalikan">
                <button type="submit" class="bg-blue-500 text-white px-5 py-2 rounded-md hover:bg-blue-600">Konfirmasi Pengembalian</button>
            </form>
        <?php endif; ?>
    </div>
    
    <div class="mt-6">
        <a href="dashboard.php?page=peminjaman" class="text-indigo-600 hover:underline">&larr; Kembali ke Daftar Peminjaman</a>
    </div>
</div>