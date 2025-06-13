<?php
// Pastikan file ini hanya di-include, bukan diakses langsung
if(!defined('DB_SERVER')) {
    header("location: ../login.php");
    exit;
}

// Fungsi untuk mendapatkan count dari tabel
function getStatCount($mysqli, $query) {
    $count = 0;
    if ($result = $mysqli->query($query)) {
        $count = $result->fetch_row()[0];
        $result->free();
    }
    return $count;
}

// Query untuk statistik
$permintaan_baru = getStatCount($mysqli, "SELECT COUNT(*) FROM Peminjaman WHERE status = 'Menunggu Persetujuan'");
$alat_dipinjam = getStatCount($mysqli, "SELECT COUNT(*) FROM Peminjaman WHERE status = 'Dipinjam'");
$total_alat = getStatCount($mysqli, "SELECT COUNT(*) FROM Alat");
$total_mahasiswa = getStatCount($mysqli, "SELECT COUNT(*) FROM Mahasiswa");

?>
<h3 class="text-xl font-semibold mb-4">Dashboard Administrator</h3>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <!-- Stat Card: Permintaan Baru -->
    <a href="dashboard.php?page=peminjaman" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
        <div class="flex items-center">
            <div class="bg-yellow-100 p-4 rounded-full"><i class="fas fa-inbox fa-2x text-yellow-500"></i></div>
            <div class="ml-4">
                <p class="text-gray-500 text-sm">Permintaan Baru</p>
                <p class="text-2xl font-bold"><?php echo $permintaan_baru; ?></p>
            </div>
        </div>
    </a>
    <!-- Stat Card: Alat Dipinjam -->
    <a href="dashboard.php?page=peminjaman" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
        <div class="flex items-center">
            <div class="bg-blue-100 p-4 rounded-full"><i class="fas fa-hand-holding-hand fa-2x text-blue-500"></i></div>
            <div class="ml-4">
                <p class="text-gray-500 text-sm">Alat Dipinjam</p>
                <p class="text-2xl font-bold"><?php echo $alat_dipinjam; ?></p>
            </div>
        </div>
    </a>
    <!-- Stat Card: Total Alat -->
    <a href="dashboard.php?page=alat" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
        <div class="flex items-center">
            <div class="bg-green-100 p-4 rounded-full"><i class="fas fa-tools fa-2x text-green-500"></i></div>
            <div class="ml-4">
                <p class="text-gray-500 text-sm">Total Alat</p>
                <p class="text-2xl font-bold"><?php echo $total_alat; ?></p>
            </div>
        </div>
    </a>
    <!-- Stat Card: Total Mahasiswa -->
    <a href="#" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
        <div class="flex items-center">
            <div class="bg-purple-100 p-4 rounded-full"><i class="fas fa-users fa-2x text-purple-500"></i></div>
            <div class="ml-4">
                <p class="text-gray-500 text-sm">Total Mahasiswa</p>
                <p class="text-2xl font-bold"><?php echo $total_mahasiswa; ?></p>
            </div>
        </div>
    </a>
</div>
