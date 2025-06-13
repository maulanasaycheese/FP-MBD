<?php
if(!defined('DB_SERVER')) { header("location: ../login.php"); exit; }

$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'];
$message = "";

// Handle form submission untuk memperbarui profil
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form (tanpa email)
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $no_hp = trim($_POST['no_hp']);
    $prodi_or_jabatan = trim($_POST['prodi_or_jabatan']);
    $password = $_POST['password'];

    // Update data profil utama (tanpa email)
    if ($user_role == 'mahasiswa') {
        $sql = "UPDATE Mahasiswa SET Nama=?, username_mhs=?, no_hp_mhs=?, prodi_mhs=? WHERE NRP=?";
    } else {
        $sql = "UPDATE Pegawai SET nama_pegawai=?, username=?, no_hp_pegawai=?, jabatan_pegawai=? WHERE ID_pegawai=?";
    }
    
    if($stmt = $mysqli->prepare($sql)){
        // bind_param sekarang hanya memiliki 5 parameter string
        $stmt->bind_param("sssss", $nama, $username, $no_hp, $prodi_or_jabatan, $user_id);
        if ($stmt->execute()) {
            $_SESSION['nama'] = $nama; // Update nama di session jika berubah
            $message = "<div class='alert alert-success'>Profil berhasil diperbarui.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Gagal memperbarui profil. Username mungkin sudah digunakan.</div>";
        }
        $stmt->close();
    }
    
    // Logika untuk mengubah password (tidak berubah)
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $message .= "<div class='alert alert-danger'>Password minimal 6 karakter.</div>";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql_pass = ($user_role == 'mahasiswa') ? "UPDATE Mahasiswa SET password_mhs=? WHERE NRP=?" : "UPDATE Pegawai SET password=? WHERE ID_pegawai=?";

            if($stmt_pass = $mysqli->prepare($sql_pass)){
                $stmt_pass->bind_param("ss", $hashed_password, $user_id);
                if($stmt_pass->execute()) {
                    $message .= "<div class='alert alert-success'>Password berhasil diubah.</div>";
                }
                $stmt_pass->close();
            }
        }
    }
}

// Ambil data profil terbaru untuk ditampilkan di form
$sql_user = ($user_role == 'mahasiswa') ? "SELECT Nama, Email_mhs as email, username_mhs as username, no_hp_mhs as no_hp, prodi_mhs as prodi_jabatan FROM Mahasiswa WHERE NRP = ?" : "SELECT nama_pegawai as Nama, email_pegawai as email, username, no_hp_pegawai as no_hp, jabatan_pegawai as prodi_jabatan FROM Pegawai WHERE ID_pegawai = ?";

if($stmt_user = $mysqli->prepare($sql_user)){
    $stmt_user->bind_param("s", $user_id);
    $stmt_user->execute();
    $user_data = $stmt_user->get_result()->fetch_assoc();
    $stmt_user->close();
}
?>
<div class="bg-white p-6 rounded-lg shadow">
    <h3 class="text-xl font-semibold mb-4">Edit Profil</h3>
    <?php echo $message; ?>
    <form action="dashboard.php?page=edit_profil" method="post" class="space-y-4 max-w-lg">
        <div>
            <label class="block font-medium text-gray-700">Nama Lengkap</label>
            <input type="text" name="nama" value="<?php echo htmlspecialchars($user_data['Nama'] ?? ''); ?>" class="form-input mt-1">
        </div>
        
        <div>
            <label class="block font-medium text-gray-700">Email</label>
            <p class="mt-1 p-2 bg-gray-100 rounded-md text-gray-600"><?php echo htmlspecialchars($user_data['email'] ?? ''); ?></p>
        </div>

        <div>
            <label class="block font-medium text-gray-700">Username</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($user_data['username'] ?? ''); ?>" class="form-input mt-1">
        </div>
        <div>
            <label class="block font-medium text-gray-700">Nomor HP</label>
            <input type="text" name="no_hp" value="<?php echo htmlspecialchars($user_data['no_hp'] ?? ''); ?>" class="form-input mt-1">
        </div>
        <div>
            <label class="block font-medium text-gray-700"><?php echo ($user_role == 'mahasiswa') ? 'Program Studi' : 'Jabatan'; ?></label>
            <input type="text" name="prodi_or_jabatan" value="<?php echo htmlspecialchars($user_data['prodi_jabatan'] ?? ''); ?>" class="form-input mt-1">
        </div>
        <hr/>
        <div>
            <label class="block font-medium text-gray-700">Ubah Password</label>
            <input type="password" name="password" placeholder="Isi hanya jika ingin mengubah password" class="form-input mt-1">
        </div>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </form>
</div>