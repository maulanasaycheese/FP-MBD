<?php
if(!defined('DB_SERVER')) { header("location: ../login.php"); exit; }
?>
<div class="bg-white p-6 rounded-lg shadow">
    <h3 class="text-xl font-semibold mb-4">Manajemen Peminjaman</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Peminjam</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Tgl Pinjam</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
            <?php
                $sql = "SELECT p.ID_peminjaman, m.Nama, p.tanggal_pinjam, p.status 
                        FROM Peminjaman p 
                        JOIN Mahasiswa m ON p.NRP = m.NRP 
                        ORDER BY p.tanggal_pinjam DESC";
                if ($result = $mysqli->query($sql)) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $status_class = 'bg-gray-100 text-gray-800'; // Default
                    if ($row['status'] == 'Menunggu Persetujuan') {
                        $status_class = 'bg-yellow-100 text-yellow-800';
                    } elseif ($row['status'] == 'Dipinjam') {
                        $status_class = 'bg-blue-100 text-blue-800';
                    } elseif ($row['status'] == 'Diajukan untuk Kembali') {
                        $status_class = 'bg-purple-100 text-purple-800 animate-pulse'; // Diberi warna ungu dan animasi pulse
                    } elseif ($row['status'] == 'Dikembalikan') {
                        $status_class = 'bg-green-100 text-green-800';
                    }

                    echo "<tr>
                        <td class='px-6 py-4'>" . htmlspecialchars($row['Nama']) . "</td>
                        <td class='px-6 py-4'>" . date('d M Y H:i', strtotime($row['tanggal_pinjam'])) . "</td>
                        <td class='px-6 py-4 whitespace-nowrap'>
                            <span class='px-2 inline-flex text-xs leading-5 font-semibold rounded-full " . $status_class . "'>"
                                . htmlspecialchars($row['status']) .
                            "</span>
                        </td>
                        <td class='px-6 py-4 text-sm font-medium'>
                            <a href='dashboard.php?page=detail_peminjaman&id=" . $row['ID_peminjaman'] . "' class='text-indigo-600 hover:text-indigo-900'>Detail</a>
                        </td>
                    </tr>";
                }
                } else { echo "<tr><td colspan='4' class='text-center px-6 py-4'>Tidak ada data peminjaman.</td></tr>"; }
                    $result->free();
                }
            ?>
            </tbody>
        </table>
    </div>
</div>
