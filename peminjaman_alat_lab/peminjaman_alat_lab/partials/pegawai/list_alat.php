<?php
if(!defined('DB_SERVER')) { header("location: ../login.php"); exit; }

// A map to handle the 'kondisi_alat' conversion from text to integer
$kondisi_map = [
    'Baik' => 0,
    'Perlu Perbaikan' => 1,
    'Rusak' => 2
];
// And the reverse map for displaying the integer as text
$kondisi_display_map = array_flip($kondisi_map);

// Initialize variables
$id_alat = $nama_alat = $lokasi_penyimpanan = "";
$kondisi_alat_val = 0; // Default to 'Baik'
$stok_alat = 0;
$form_error = "";
$action = $_GET['action'] ?? 'list';
$page_title = "Manajemen Alat";

// Handle form submissions for CREATE and UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $id_alat = trim($_POST['id_alat']);
    $nama_alat = trim($_POST['nama_alat']);
    $kondisi_text = trim($_POST['kondisi_alat']);
    $lokasi_penyimpanan = trim($_POST['lokasi_penyimpanan']);
    $stok_alat = (int)$_POST['stok_alat'];
    
    // Convert text condition from form to integer for database
    $kondisi_alat_int = $kondisi_map[$kondisi_text] ?? 0;

    if (empty($nama_alat) || empty($lokasi_penyimpanan)) {
        $form_error = "Nama alat dan lokasi tidak boleh kosong.";
    } else {
        if (!empty($id_alat)) { // UPDATE action
            $sql = "UPDATE Alat SET nama_alat=?, kondisi_alat=?, lokasi_penyimpanan=?, stok_alat=? WHERE ID_alat=?";
            if($stmt = $mysqli->prepare($sql)){
                $stmt->bind_param("sisis", $nama_alat, $kondisi_alat_int, $lokasi_penyimpanan, $stok_alat, $id_alat);
                if($stmt->execute()) {
                    $_SESSION['flash_message'] = "Alat berhasil diperbarui.";
                    header("location: dashboard.php?page=alat");
                    exit;
                }
            }
        } else { // CREATE action
            // 1. Find the highest current ID number
            $last_id_result = $mysqli->query("SELECT MAX(CAST(SUBSTRING(ID_alat, 4) AS UNSIGNED)) as max_id FROM Alat");
            $last_id_row = $last_id_result->fetch_assoc();
            $next_id_num = ($last_id_row['max_id'] ?? -1) + 1;

            // 2. Format the new ID string
            $new_id = "ALT" . str_pad($next_id_num, 3, '0', STR_PAD_LEFT);

            // 3. Prepare the INSERT statement
            $sql = "INSERT INTO Alat (ID_alat, nama_alat, kondisi_alat, lokasi_penyimpanan, stok_alat) VALUES (?, ?, ?, ?, ?)";
            
            if($stmt = $mysqli->prepare($sql)){
                $stmt->bind_param("ssisi", $new_id, $nama_alat, $kondisi_alat_int, $lokasi_penyimpanan, $stok_alat);
                if($stmt->execute()) {
                    $_SESSION['flash_message'] = "Alat baru berhasil ditambahkan.";
                    header("location: dashboard.php?page=alat");
                    exit;
                }
            }
        }
        $form_error = "Terjadi kesalahan. Silakan coba lagi.";
        if(isset($stmt)) $stmt->close();
    }
}

// Handle DELETE action
if ($action === 'delete' && isset($_GET['id'])) {
    // Keamanan: Pastikan hanya kepala lab yang bisa menghapus
    if ($_SESSION['peran_pegawai'] !== 'kepala_lab') {
        $_SESSION['flash_message'] = "Error: Anda tidak memiliki hak akses untuk menghapus data.";
        header("location: dashboard.php?page=alat");
        exit;
    }

    $id_alat = trim($_GET['id']);
    $sql = "DELETE FROM Alat WHERE ID_alat = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("s", $id_alat);
        if($stmt->execute()) {
            $_SESSION['flash_message'] = "Alat berhasil dihapus.";
            header("location: dashboard.php?page=alat");
            exit;
        }
        $stmt->close();
    }
}

// Populate form for EDIT action
if ($action === 'edit' && isset($_GET['id'])) {
    $page_title = "Edit Alat";
    $sql = "SELECT * FROM Alat WHERE ID_alat = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("s", $_GET['id']);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                $id_alat = $row['ID_alat'];
                $nama_alat = $row['nama_alat'];
                $kondisi_alat_val = $row['kondisi_alat'];
                $lokasi_penyimpanan = $row['lokasi_penyimpanan'];
                $stok_alat = $row['stok_alat'];
            }
        }
        $stmt->close();
    }
} elseif ($action === 'add') {
    $page_title = "Tambah Alat Baru";
}
?>

<div class="bg-white p-6 rounded-lg shadow">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-semibold"><?php echo $page_title; ?></h3>
        
        <?php if ($action === 'list'): ?>
        <a href="dashboard.php?page=alat&action=add" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
            <i class="fas fa-plus mr-2"></i>Tambah Alat
        </a>
        <?php endif; ?>
    </div>

    <?php  
    if (isset($_SESSION['flash_message'])) {
        echo '<div class="alert alert-success">' . $_SESSION['flash_message'] . '</div>';
        unset($_SESSION['flash_message']);
    }
    ?>

    <?php if ($action === 'add' || $action === 'edit'): ?>
        <?php if(!empty($form_error)) echo '<div class="alert alert-danger">' . $form_error . '</div>'; ?>
        <form action="dashboard.php?page=alat" method="post" class="space-y-4 max-w-lg">
            <input type="hidden" name="id_alat" value="<?php echo $id_alat; ?>">
            <input type="text" name="nama_alat" value="<?php echo htmlspecialchars($nama_alat); ?>" placeholder="Nama Alat" class="form-input" required>
            <input type="text" name="lokasi_penyimpanan" value="<?php echo htmlspecialchars($lokasi_penyimpanan); ?>" placeholder="Lokasi Penyimpanan" class="form-input" required>
            <input type="number" name="stok_alat" value="<?php echo $stok_alat; ?>" placeholder="Stok" class="form-input" required>
            <select name="kondisi_alat" class="form-input">
                <option value="Baik" <?php if($kondisi_alat_val == 0) echo 'selected'; ?>>Baik</option>
                <option value="Perlu Perbaikan" <?php if($kondisi_alat_val == 1) echo 'selected'; ?>>Perlu Perbaikan</option>
                <option value="Rusak" <?php if($kondisi_alat_val == 2) echo 'selected'; ?>>Rusak</option>
            </select>
            <div class="flex items-center space-x-4 pt-2">
                <button type="submit" name="submit" class="bg-indigo-600 text-white px-5 py-2 rounded-md hover:bg-indigo-700">Simpan</button>
                <a href="dashboard.php?page=alat" class="bg-gray-200 text-gray-800 px-5 py-2 rounded-md hover:bg-gray-300">Batal</a>
            </div>
        </form>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Nama Alat</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Lokasi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Stok</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Kondisi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                <?php
                    $sql = "SELECT ID_alat, nama_alat, lokasi_penyimpanan, stok_alat, kondisi_alat 
                            FROM Alat 
                            ORDER BY CAST(SUBSTRING(nama_alat, 5) AS UNSIGNED), nama_alat ASC";
                    if ($result = $mysqli->query($sql)) {
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $kondisi_text = $kondisi_display_map[$row['kondisi_alat']] ?? 'Tidak Diketahui';
                                echo "<tr>";
                                echo "  <td class='px-6 py-4'>" . htmlspecialchars($row['nama_alat']) . "</td>";
                                echo "  <td class='px-6 py-4'>" . htmlspecialchars($row['lokasi_penyimpanan']) . "</td>";
                                echo "  <td class='px-6 py-4'>" . $row['stok_alat'] . "</td>";
                                echo "  <td class='px-6 py-4'>" . htmlspecialchars($kondisi_text) . "</td>";
                                echo "  <td class='px-6 py-4 text-sm font-medium'>";
                                echo "      <a href='dashboard.php?page=alat&action=edit&id=" . $row['ID_alat'] . "' class='text-indigo-600 hover:text-indigo-900'>Edit</a>";
                                
                                // MODIFIKASI FINAL: Tombol hapus hanya untuk kepala_lab
                                if ($_SESSION['peran_pegawai'] == 'kepala_lab') {
                                    echo " <a href='dashboard.php?page=alat&action=delete&id=" . $row['ID_alat'] . "' class='text-red-600 hover:text-red-900 ml-4' onclick='return confirm(\"Anda yakin ingin menghapus alat ini?\");'>Hapus</a>";
                                }

                                echo "  </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center px-6 py-4'>Tidak ada data alat.</td></tr>";
                        }
                        $result->free();
                    }
                ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>