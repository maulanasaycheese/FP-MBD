<?php
include 'templates/header.php'; // Termasuk header dan cek sesi

$page = $_GET['page'] ?? 'dashboard';

// Routing untuk halaman yang bisa diakses semua peran
if ($page === 'edit_profil') {
    include 'partials/shared/edit_profil.php';
}
// Routing untuk Pegawai
else if ($_SESSION["role"] == "pegawai") {
    // Navigasi untuk Pegawai
?>
    <div class="mb-4 border-b border-gray-200">
        <nav class="flex space-x-4" aria-label="Tabs">
            <a href="dashboard.php?page=dashboard" class="tab-btn <?php echo $page == 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
            <a href="dashboard.php?page=peminjaman" class="tab-btn <?php echo $page == 'peminjaman' ? 'active' : ''; ?>">Peminjaman</a>
            <a href="dashboard.php?page=alat" class="tab-btn <?php echo $page == 'alat' ? 'active' : ''; ?>">Alat</a>
            <a href="dashboard.php?page=showcase_query" class="tab-btn <?php echo $page == 'showcase_query' ? 'active' : ''; ?>">Showcase Query</a>
        </nav>
    </div>
<?php
    // Switch untuk konten halaman Pegawai
    switch ($page) {
        case 'peminjaman': include 'partials/pegawai/list_peminjaman.php'; break;
        case 'detail_peminjaman': include 'partials/pegawai/detail_peminjaman.php'; break;
        case 'alat': include 'partials/pegawai/list_alat.php'; break;
        case 'laporan': include 'partials/pegawai/laporan.php'; break;
        case 'showcase_query': include 'partials/pegawai/showcase_query.php'; break;
        case 'dashboard':
        default: 
            include 'partials/pegawai/dashboard.php'; break;
    }
} 
// Routing untuk Mahasiswa
else { 
?>
    <div class="mb-4 border-b border-gray-200">
        <nav class="flex space-x-4" aria-label="Tabs">
            <a href="dashboard.php?page=katalog" class="tab-btn <?php echo $page == 'katalog' || $page == 'dashboard' ? 'active' : ''; ?>">Katalog Alat</a>
            <a href="dashboard.php?page=riwayat" class="tab-btn <?php echo $page == 'riwayat' ? 'active' : ''; ?>">Peminjaman Saya</a>
        </nav>
    </div>
<?php
    // Switch untuk konten halaman Mahasiswa
    switch ($page) {
        case 'riwayat': include 'partials/mahasiswa/riwayat_peminjaman.php'; break;
        case 'katalog':
        default: 
            include 'partials/mahasiswa/katalog.php'; break;
    }
}

include 'templates/footer.php';
?>
<style>.active { color: #4f46e5; border-bottom: 2px solid #4f46e5; }</style>