<?php
require_once "config.php";
$error = "";

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_or_email = trim($_POST["username_or_email"]);
    $password = trim($_POST["password"]);

    // Coba login sebagai Pegawai
    // MODIFIKASI: Ambil juga kolom 'peran'
    $sql_pegawai = "SELECT ID_pegawai, nama_pegawai, password, peran FROM Pegawai WHERE email_pegawai = ? OR username = ?";
    if ($stmt_pegawai = $mysqli->prepare($sql_pegawai)) {
        $stmt_pegawai->bind_param("ss", $username_or_email, $username_or_email);
        if ($stmt_pegawai->execute()) {
            $result = $stmt_pegawai->get_result();
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $user['ID_pegawai'];
                    $_SESSION["nama"] = $user['nama_pegawai'];
                    $_SESSION["role"] = "pegawai";
                    // BARU: Simpan peran spesifik pegawai ke dalam session
                    $_SESSION["peran_pegawai"] = $user['peran']; 
                    header("location: dashboard.php");
                    exit();
                }
            }
        }
        $stmt_pegawai->close();
    }

    // Coba login sebagai Mahasiswa
    // MODIFIED: Query sekarang mencari di kolom Email_mhs ATAU username_mhs
    $sql_mahasiswa = "SELECT NRP, Nama, password_mhs FROM Mahasiswa WHERE Email_mhs = ? OR username_mhs = ?";
    if ($stmt_mahasiswa = $mysqli->prepare($sql_mahasiswa)) {
        // MODIFIED: Bind parameter yang sama dua kali
        $stmt_mahasiswa->bind_param("ss", $username_or_email, $username_or_email);
        if ($stmt_mahasiswa->execute()) {
            $result = $stmt_mahasiswa->get_result();
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password_mhs'])) {
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $user['NRP'];
                    $_SESSION["nama"] = $user['Nama'];
                    $_SESSION["role"] = "mahasiswa";
                    header("location: dashboard.php");
                    exit();
                }
            }
        }
        $stmt_mahasiswa->close();
    }
    
    $error = "Username/Email atau password Anda salah.";
    $mysqli->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-gray-100">
    <div class="flex items-center justify-center min-h-screen">
        <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-xl shadow-lg">
            <h2 class="text-2xl font-bold text-center">Login Sistem Peminjaman</h2>
            <?php if(!empty($error)) echo '<div class="alert alert-danger">' . $error . '</div>'; ?>
            
            <form action="login.php" method="post" class="mt-8 space-y-6">
                <input name="username_or_email" type="text" required class="form-input" placeholder="Email atau Username">
                <input name="password" type="password" required class="form-input" placeholder="Password">
                <button type="submit" class="w-full py-2 text-white bg-indigo-600 rounded-md hover:bg-indigo-700">Login</button>
            </form>
            <p class="mt-4 text-center text-sm">Belum punya akun? <a href="register.php" class="font-medium text-indigo-600">Registrasi</a></p>
        </div>
    </div>
</body>
</html>