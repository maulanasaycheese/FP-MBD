<?php
require_once "config.php";
$error = $success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];
    $username = trim($_POST['username']);

    // Validasi hanya untuk data yang wajib diisi
    if (empty($nama) || empty($email) || empty($password) || empty($role)) {
        $error = "Nama, Email, Password, dan Peran wajib diisi.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal harus 6 karakter.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        if ($role == 'mahasiswa') {
            $nrp = trim($_POST['nrp']);
            if (empty($nrp)) {
                $error = "NRP wajib diisi untuk mahasiswa.";
            } else {
                // Query disederhanakan, hanya menyimpan data inti
                $sql = "INSERT INTO Mahasiswa (NRP, Nama, Email_mhs, username_mh, password_mh) VALUES (?, ?, ?, ?, ?)";
                if ($stmt = $mysqli->prepare($sql)) {
                    $stmt->bind_param("sssss", $nrp, $nama, $email, $username, $hashed_password);
                    if ($stmt->execute()) {
                        $success = "Registrasi mahasiswa berhasil! Silakan login.";
                    } else {
                        $error = "Gagal mendaftar. Username, Email atau NRP mungkin sudah digunakan.";
                    }
                    $stmt->close();
                }
            }
        } elseif ($role == 'pegawai') {
            $id_pegawai = "PGW" . rand(100, 999);
            // Query disederhanakan, hanya menyimpan data inti
            $sql = "INSERT INTO Pegawai (ID_pegawai, nama_pegawai, email_pegawai, username, password) VALUES (?, ?, ?, ?, ?)";
            if ($stmt = $mysqli->prepare($sql)) {
                $stmt->bind_param("sssss", $id_pegawai, $nama, $email, $username, $hashed_password);
                if ($stmt->execute()) {
                    $success = "Registrasi pegawai berhasil! Silakan login.";
                } else {
                    $error = "Gagal mendaftar. Username atau Email mungkin sudah digunakan.";
                }
                $stmt->close();
            }
        }
    }
    if (isset($mysqli)) {
        $mysqli->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Registrasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-gray-100">
    <div class="flex items-center justify-center min-h-screen py-12">
        <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-xl shadow-lg">
            <h2 class="text-2xl font-bold text-center">Registrasi Akun Baru</h2>
            <?php if(!empty($error)) echo '<div class="alert alert-danger">' . $error . '</div>'; ?>
            <?php if(!empty($success)) echo '<div class="alert alert-success">' . $success . '</div>'; ?>
            <form action="register.php" method="post" class="mt-8 space-y-4">
                <input name="nama" type="text" placeholder="Nama Lengkap (Wajib)" required class="form-input">
                <input name="email" type="email" placeholder="Email (Wajib)" required class="form-input">
                <input name="username" type="text" placeholder="Username (Opsional, untuk login)" class="form-input">
                <input name="password" type="password" placeholder="Password (Wajib, min. 6 karakter)" required class="form-input">
                <select name="role" id="role-select" required class="form-input">
                    <option value="mahasiswa">Mahasiswa</option>
                    <option value="pegawai">Pegawai (Admin)</option>
                </select>
                <div id="mahasiswa-fields">
                    <input name="nrp" type="text" placeholder="NRP (Wajib untuk mahasiswa)" class="form-input">
                </div>
                <button type="submit" class="w-full py-2 text-white bg-indigo-600 rounded-md hover:bg-indigo-700">Registrasi</button>
            </form>
            <p class="mt-4 text-center text-sm">Sudah punya akun? <a href="login.php" class="font-medium text-indigo-600">Login di sini</a></p>
        </div>
    </div>
    <script>
        const roleSelect = document.getElementById('role-select');
        const mhsFields = document.getElementById('mahasiswa-fields');
        const nrpInput = mhsFields.querySelector('input[name="nrp"]');

        function toggleNrpField() {
            if (roleSelect.value === 'mahasiswa') {
                mhsFields.style.display = 'block';
                nrpInput.required = true;
            } else {
                mhsFields.style.display = 'none';
                nrpInput.required = false;
            }
        }
        toggleNrpField();
        roleSelect.addEventListener('change', toggleNrpField);
    </script>
</body>
</html>