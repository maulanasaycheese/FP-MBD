<?php
if(!defined('DB_SERVER')) { header("location: ../login.php"); exit; }

$pinjam_success = "";
$pinjam_error = "";

// Handle the "Pinjam" button form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pinjam_alat_id'])) {
    $alat_id_to_borrow = trim($_POST['pinjam_alat_id']);
    $mahasiswa_nrp = $_SESSION['id'];

    $peminjaman_id = "PMJ" . rand(100, 999); // Simple unique ID generation

    // Start transaction
    $mysqli->begin_transaction();

    try {
        // 1. Create the main Peminjaman record
        $sql_peminjaman = "INSERT INTO Peminjaman (ID_peminjaman, tanggal_pinjam, status, NRP) VALUES (?, NOW(), 'Menunggu Persetujuan', ?)";
        $stmt = $mysqli->prepare($sql_peminjaman);
        $stmt->bind_param("ss", $peminjaman_id, $mahasiswa_nrp);
        $stmt->execute();
        $stmt->close();

        // 2. Link the tool in Peminjaman_Alat
        $sql_peminjaman_alat = "INSERT INTO Peminjaman_Alat (ID_peminjaman, ID_alat) VALUES (?, ?)";
        $stmt_alat = $mysqli->prepare($sql_peminjaman_alat);
        $stmt_alat->bind_param("ss", $peminjaman_id, $alat_id_to_borrow);
        $stmt_alat->execute();
        $stmt_alat->close();

        // If all queries succeed, commit the transaction
        $mysqli->commit();
        $pinjam_success = "Permintaan peminjaman berhasil dikirim!";

    } catch (mysqli_sql_exception $exception) {
        // If any query fails, roll back the transaction
        $mysqli->rollback();
        $pinjam_error = "Gagal meminjam alat. Silakan coba lagi.";
    }
}

// Map for displaying tool condition
$kondisi_display_map = [
    0 => 'Baik',
    1 => 'Perlu Perbaikan',
    2 => 'Rusak'
];
?>
<div class="bg-white p-6 rounded-lg shadow">
    <h3 class="text-xl font-semibold mb-4">Katalog Alat Laboratorium</h3>

    <?php if(!empty($pinjam_success)) echo '<div class="alert alert-success">' . $pinjam_success . '</div>'; ?>
    <?php if(!empty($pinjam_error)) echo '<div class="alert alert-danger">' . $pinjam_error . '</div>'; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php
        // This ORDER BY clause fixes the sorting issue, addressing point #5
        $sql = "SELECT ID_alat, nama_alat, lokasi_penyimpanan, stok_alat, kondisi_alat 
                FROM Alat 
                WHERE stok_alat > 0 
                ORDER BY CAST(SUBSTRING(nama_alat, 5) AS UNSIGNED), nama_alat ASC";
        if ($result = $mysqli->query($sql)) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $kondisi_text = $kondisi_display_map[$row['kondisi_alat']] ?? 'Tidak Diketahui';
        ?>
            <div class="border rounded-lg p-4 flex flex-col justify-between hover:shadow-lg transition-shadow">
                <div>
                    <h4 class="font-bold text-lg"><?php echo htmlspecialchars($row['nama_alat']); ?></h4>
                    <p class="text-sm text-gray-600">Lokasi: <?php echo htmlspecialchars($row['lokasi_penyimpanan']); ?></p>
                    <p class="text-sm text-gray-500">Kondisi: <?php echo htmlspecialchars($kondisi_text); ?></p>
                </div>
                <div class="mt-4 flex justify-between items-center">
                    <span class="font-semibold">Stok: <?php echo $row['stok_alat']; ?></span>
                    
                    <form method="POST" action="dashboard.php" class="inline">
                        <input type="hidden" name="pinjam_alat_id" value="<?php echo $row['ID_alat']; ?>">
                        <button type="submit" class="bg-indigo-500 text-white px-4 py-2 text-sm rounded-md hover:bg-indigo-600">Pinjam</button>
                    </form>
                </div>
            </div>
        <?php
                }
            } else {
                echo "<p class='col-span-full'>Saat ini tidak ada alat yang tersedia untuk dipinjam.</p>";
            }
            $result->free();
        }
        ?>
    </div>
</div>