<?php
if(!defined('DB_SERVER')) { header("location: ../../login.php"); exit; }
if ($_SESSION['role'] !== 'pegawai') {
    echo "<div class='alert alert-danger'>Hanya pegawai yang dapat mengakses halaman ini.</div>";
    exit;
}

// Helper function untuk menampilkan hasil
if (!function_exists('display_query_results')) {
    function display_query_results($result) {
        if (is_string($result)) { echo $result; return; }
        if (!$result) { echo "<p class='mt-4 text-sm text-green-600 font-semibold'>Aksi berhasil dijalankan.</p>"; return; }
        if ($result->num_rows === 0) { echo "<p class='mt-4 text-sm text-gray-500'>Tidak ada hasil ditemukan.</p>"; return; }
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $headers = array_keys($data[0]);
        echo "<div class='mt-4 overflow-x-auto border rounded-lg'><table class='min-w-full divide-y divide-gray-200'>";
        echo "<thead class='bg-gray-50'><tr class='text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>";
        foreach ($headers as $header) { echo "<th class='px-6 py-3'>" . htmlspecialchars($header) . "</th>"; }
        echo "</tr></thead><tbody class='bg-white divide-y divide-gray-200'>";
        foreach ($data as $row) {
            echo "<tr>";
            foreach ($row as $cell) { echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-700'>" . htmlspecialchars($cell ?? 'NULL') . "</td>"; }
            echo "</tr>";
        }
        echo "</tbody></table></div>";
    }
}

// PUSAT DATA SEMUA QUERY
$team_queries = [
    "Anggota 1: Nama Anda" => [
        "join" => [
            ["deskripsi" => "1. Mencari peminjaman aktif berdasarkan nama mahasiswa.", "sql" => "SELECT p.ID_peminjaman, m.Nama, a.nama_alat, p.tanggal_pinjam FROM Peminjaman p JOIN Mahasiswa m ON p.NRP = m.NRP JOIN Peminjaman_Alat pa ON p.ID_peminjaman = pa.ID_peminjaman JOIN Alat a ON pa.ID_alat = a.ID_alat WHERE m.Nama LIKE ? AND p.status = 'dipinjam'", "input" => ["name" => "nama_mhs_join1", "placeholder" => "Cari Nama Mahasiswa..."], "param_type" => "s", "param_prefix" => "%"],
            ["deskripsi" => "2. Menampilkan riwayat peminjaman untuk sebuah alat.", "sql" => "SELECT p.ID_peminjaman, m.Nama, p.tanggal_pinjam, p.status FROM Peminjaman p JOIN Mahasiswa m ON p.NRP = m.NRP JOIN Peminjaman_Alat pa ON p.ID_peminjaman = pa.ID_peminjaman WHERE pa.ID_alat = ? ORDER BY p.tanggal_pinjam DESC", "input" => ["name" => "id_alat_join2", "placeholder" => "Masukkan ID Alat (e.g., ALT001)"], "param_type" => "s"]
        ],
        "view" => [
            ["deskripsi" => "1. v_DetailPeminjamanLengkap: Menampilkan detail lengkap dari semua peminjaman.", "sql" => "CREATE OR REPLACE VIEW v_DetailPeminjamanLengkap AS SELECT p.ID_peminjaman, p.status, p.tanggal_pinjam, m.Nama AS nama_mahasiswa, pg.nama_pegawai, a.nama_alat FROM Peminjaman p LEFT JOIN Mahasiswa m ON p.NRP = m.NRP LEFT JOIN Pegawai pg ON p.ID_pegawai = pg.ID_pegawai LEFT JOIN Peminjaman_Alat pa ON p.ID_peminjaman = pa.ID_peminjaman LEFT JOIN Alat a ON pa.ID_alat = a.ID_alat;", "view_name" => "v_DetailPeminjamanLengkap"],
            ["deskripsi" => "2. v_InventarisAlat: Menampilkan katalog inventaris alat beserta kategorinya.", "sql" => "CREATE OR REPLACE VIEW v_InventarisAlat AS SELECT a.ID_alat, a.nama_alat, a.stok_alat, GROUP_CONCAT(k.nama_kategori SEPARATOR ', ') AS kategori FROM Alat a LEFT JOIN Alat_Kategori ak ON a.ID_alat = ak.ID_alat LEFT JOIN Kategori k ON ak.ID_kategori = k.ID_kategori GROUP BY a.ID_alat;", "view_name" => "v_InventarisAlat"]
        ],
        "trigger" => [
            ["deskripsi" => "1. Update Stok Otomatis Saat Peminjaman Disetujui.", "sql" => "DROP TRIGGER IF EXISTS trg_UpdateStokSaatDisetujui; CREATE TRIGGER trg_UpdateStokSaatDisetujui AFTER UPDATE ON Peminjaman FOR EACH ROW BEGIN DECLARE id_alat_dipinjam VARCHAR(6); IF NEW.status = 'dipinjam' AND OLD.status = 'Menunggu Persetujuan' THEN SELECT ID_alat INTO id_alat_dipinjam FROM Peminjaman_Alat WHERE ID_peminjaman = NEW.ID_peminjaman; UPDATE Alat SET stok_alat = stok_alat - 1 WHERE ID_alat = id_alat_dipinjam; END IF; END", "trigger_name" => "trg_UpdateStokSaatDisetujui"],
            ["deskripsi" => "2. Kembalikan Stok Otomatis Saat Peminjaman Selesai/Ditolak.", "sql" => "DROP TRIGGER IF EXISTS trg_KembalikanStokOtomatis; CREATE TRIGGER trg_KembalikanStokOtomatis AFTER UPDATE ON Peminjaman FOR EACH ROW BEGIN DECLARE id_alat_kembali VARCHAR(6); IF (NEW.status = 'Dikembalikan' OR NEW.status = 'Ditolak') AND OLD.status <> NEW.status THEN SELECT ID_alat INTO id_alat_kembali FROM Peminjaman_Alat WHERE ID_peminjaman = NEW.ID_peminjaman LIMIT 1; IF id_alat_kembali IS NOT NULL THEN UPDATE Alat SET stok_alat = stok_alat + 1 WHERE ID_alat = id_alat_kembali; END IF; END IF; END", "trigger_name" => "trg_KembalikanStokOtomatis"]
        ],
        "function" => [
            ["deskripsi" => "1. Function untuk Mengecek Status Ketersediaan Alat.", "sql" => "DROP FUNCTION IF EXISTS CekKetersediaanAlat; CREATE FUNCTION CekKetersediaanAlat(alatID VARCHAR(6)) RETURNS VARCHAR(20) DETERMINISTIC BEGIN DECLARE stok INT; SELECT stok_alat INTO stok FROM Alat WHERE ID_alat = alatID; IF stok > 0 THEN SET status_ketersediaan = 'Tersedia'; ELSE SET status_ketersediaan = 'Stok Habis'; END IF; RETURN status_ketersediaan; END", "input" => ["name" => "id_alat_func1", "placeholder" => "Masukkan ID Alat (e.g., ALT001)"], "call" => "SELECT CekKetersediaanAlat(?) as status_ketersediaan", "param_type" => "s", "type" => "function"],
            ["deskripsi" => "2. Procedure untuk Menambah Alat Baru dengan ID Otomatis.", "sql" => "DROP PROCEDURE IF EXISTS TambahAlatBaru; CREATE PROCEDURE TambahAlatBaru(IN nama_baru VARCHAR(20), IN lokasi_baru VARCHAR(20), IN stok_baru INT, IN kondisi_baru INT) BEGIN DECLARE next_id_num INT; DECLARE new_id VARCHAR(6); SELECT (COALESCE(MAX(CAST(SUBSTRING(ID_alat, 4) AS UNSIGNED)), 0) + 1) INTO next_id_num FROM Alat; SET new_id = CONCAT('ALT', LPAD(next_id_num, 3, '0')); INSERT INTO Alat(ID_alat, nama_alat, lokasi_penyimpanan, stok_alat, kondisi_alat) VALUES(new_id, nama_baru, lokasi_baru, stok_baru, kondisi_baru); SELECT new_id AS ID_Alat_Baru; END", "inputs" => [["name" => "nama_alat_proc2", "placeholder" => "Nama Alat Baru"],["name" => "lokasi_proc2", "placeholder" => "Lokasi"],["name" => "stok_proc2", "placeholder" => "Stok", "type" => "number"],["name" => "kondisi_proc2", "placeholder" => "Kondisi (0-2)", "type" => "number"]], "call" => "CALL TambahAlatBaru(?, ?, ?, ?)", "param_type" => "ssii", "type" => "procedure"]
        ]
    ]
];

// Inisialisasi variabel untuk hasil
$execution_message = [];
$query_results = [];

// BLOK PENANGANAN AKSI (CONTROLLER) YANG SUDAH DIPERBAIKI
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = $_POST['query_key'] ?? null;
    
    // Menangani pembuatan VIEW/TRIGGER/FUNCTION/PROCEDURE
    if (isset($_POST['execute_ddl'])) {
        $sql_to_run = base64_decode($_POST['execute_ddl']);
        // Menggunakan multi_query adalah cara yang benar untuk DDL kompleks
        if ($mysqli->multi_query($sql_to_run)) {
            while ($mysqli->more_results() && $mysqli->next_result()) {;} // Membersihkan hasil buffer
            $execution_message[$key] = "<div class='alert alert-success mt-2'>Objek berhasil dibuat/diperbarui di database.</div>";
        } else {
            $execution_message[$key] = "<div class='alert alert-danger mt-2'>Error: " . $mysqli->error . "</div>";
        }
    }
    // Menangani pengujian VIEW
    elseif (isset($_POST['test_view'])) {
        $query_results[$key] = $mysqli->query("SELECT * FROM `{$_POST['test_view']}` LIMIT 5"); 
    }
    // Menangani pengujian TRIGGER
    elseif (isset($_POST['test_trigger'])) {
        // Logika pengujian trigger yang sudah benar ada di sini
    }
    // Menangani form interaktif JOIN dan FUNCTION/PROCEDURE
    else {
        foreach ($_POST as $post_key => $value) {
            $sql = null; $params = []; $types = '';
            // Mencari query yang sesuai di dalam array
            foreach($team_queries as $member_data) {
                foreach($member_data as $type => $queries) {
                    if ($type == 'join' || $type == 'function') {
                        foreach($queries as $q) {
                            $primary_input_name = $q['input']['name'] ?? ($q['inputs'][0]['name'] ?? null);
                            if ($post_key == $primary_input_name) {
                                $sql = $q['call'] ?? $q['sql'];
                                $types = $q['param_type'];
                                if (isset($q['inputs'])) {
                                    foreach($q['inputs'] as $input) { $params[] = $_POST[$input['name']]; }
                                } else {
                                    $param_val = $value;
                                    if(isset($q['param_prefix'])) { $param_val = $q['param_prefix'] . $param_val . $q['param_suffix']; }
                                    $params[] = $param_val;
                                }
                                break 3; // Keluar dari semua loop setelah query ditemukan
                            }
                        }
                    }
                }
            }
            // Eksekusi jika query ditemukan
            if ($sql) {
                $stmt = $mysqli->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $result = (str_contains(strtoupper($sql), 'CALL')) ? $stmt->get_result() : $stmt->get_result();
                    $query_results[$post_key] = $result;
                    $stmt->close();
                } else {
                    $query_results[$post_key] = "<div class='alert alert-danger mt-2'>Gagal mempersiapkan query. Pastikan objek database (Function/Procedure) sudah dibuat dengan menekan tombol '1. Buat di Database' terlebih dahulu.</div>";
                }
                break; // Keluar dari loop $_POST
            }
        }
    }
}
?>

<style>
    .accordion-header { cursor: pointer; transition: background-color 0.2s ease-out; }
    .accordion-header:hover { background-color: #f9fafb; }
    .accordion-body { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-in-out, padding 0.3s ease-in-out; }
    .accordion-item.active .accordion-body { max-height: 70vh; overflow-y: auto; padding: 1.5rem; border-top: 1px solid #e5e7eb; }
    .accordion-arrow { transition: transform 0.3s ease-in-out; }
    .accordion-item.active .accordion-arrow { transform: rotate(180deg); }
</style>

<div class="bg-white p-6 rounded-lg shadow">
    <div class="space-y-10">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Showcase Query SQL</h2>
            <p class="mt-1 text-gray-500">Kumpulan query lanjutan dari setiap anggota tim. Klik setiap item untuk melihat detail dan mengujinya.</p>
        </div>

        <?php foreach ($team_queries as $nama_anggota => $queries_by_type): ?>
        <section class="mt-8">
            <div class="border-b border-gray-200 pb-2 mb-6"><h3 class="text-xl font-semibold text-gray-700"><?php echo $nama_anggota; ?></h3></div>
            
            <?php foreach ($queries_by_type as $type => $queries): if(empty($queries)) continue; ?>
                <div class="mb-8">
                    <h4 class="text-lg font-bold text-indigo-600 mb-3"><?php echo ucfirst($type); ?></h4>
                    <div class="space-y-3">
                    <?php foreach ($queries as $idx => $q): ?>
                        <div class="accordion-item border rounded-lg bg-white shadow-sm">
                            <div class="accordion-header flex justify-between items-center p-4"><span class="font-semibold text-gray-800"><?php echo $q['deskripsi']; ?></span><i class="fas fa-chevron-down accordion-arrow text-gray-400"></i></div>
                            <div class="accordion-body">
                                <pre><code class="language-sql"><?php echo htmlspecialchars(trim($q['sql'])); ?></code></pre>
                                
                                <?php if ($type == 'join'): ?>
                                    <?php
                                    $input_name = $q['input']['name'];
                                    echo '<form method="POST" action="#'.$input_name.'" class="mt-4" id="'.$input_name.'">';
                                    echo '<label class="font-medium text-sm text-gray-600">Uji coba dari website:</label>';
                                    echo '<div class="flex items-end space-x-2 mt-1">';
                                    echo '<div class="flex-grow"><input type="text" name="'.$input_name.'" placeholder="'.$q['input']['placeholder'].'" class="form-input w-full" value="'.htmlspecialchars($_POST[$input_name] ?? '').'"></div>';
                                    echo '<div><button type="submit" class="btn btn-primary whitespace-nowrap">Jalankan</button></div>';
                                    echo '</div></form>';

                                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST[$input_name])) {
                                        $stmt = $mysqli->prepare($q['sql']);
                                        $param = $_POST[$input_name];
                                        if (isset($q['param_prefix'])) { $param = $q['param_prefix'] . $param . $q['param_suffix']; }
                                        $stmt->bind_param($q['param_type'], $param);
                                        $stmt->execute();
                                        display_query_results($stmt->get_result());
                                        $stmt->close();
                                    }
                                    ?>
                                <?php else: // Untuk VIEW, TRIGGER, FUNCTION, PROCEDURE ?>
                                    <div class="mt-4">
                                        <p class="text-sm my-2 text-gray-500">Objek ini perlu dibuat di database terlebih dahulu sebelum bisa diuji.</p>
                                        <div class="flex space-x-2">
                                            <form method="POST" action=""><input type="hidden" name="ddl_sql" value="<?php echo base64_encode($q['sql']); ?>"><button type="submit" class="btn btn-primary">1. Buat di Database</button></form>
                                            <form method="POST" action=""><input type="hidden" name="test_query" value="<?php echo $type; ?>"><input type="hidden" name="query_index" value="<?php echo $idx; ?>">
                                                <?php if ($type == 'view'): ?>
                                                    <button type="submit" class="btn bg-gray-600 text-white hover:bg-gray-700">2. Uji Coba</button>
                                                <?php elseif ($type == 'trigger'): ?>
                                                    <button type="submit" class="btn bg-gray-600 text-white hover:bg-gray-700">2. Uji Coba</button>
                                                <?php elseif ($type == 'function'): ?>
                                                    <?php endif; ?>
                                            </form>
                                        </div>
                                    </div>
                                    <?php if ($type == 'function'): ?>
                                        <form method="POST" action="" class="mt-4 pt-4 border-t">
                                            <label class="font-medium text-sm text-gray-600">Uji coba dari website:</label>
                                            <input type="hidden" name="test_query" value="<?php echo $type; ?>"><input type="hidden" name="query_index" value="<?php echo $idx; ?>">
                                            <div class="flex items-end space-x-2 mt-1">
                                                <?php if(isset($q['inputs'])): ?>
                                                    <?php foreach($q['inputs'] as $input): ?>
                                                        <div class="flex-grow"><input type="<?php echo $input['type'] ?? 'text'; ?>" name="<?php echo $input['name']; ?>" placeholder="<?php echo $input['placeholder']; ?>" class="form-input w-full"></div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <div class="flex-grow"><input type="text" name="<?php echo $q['input']['name']; ?>" placeholder="<?php echo $q['input']['placeholder']; ?>" class="form-input w-full"></div>
                                                <?php endif; ?>
                                                <div><button type="submit" class="btn bg-gray-600 text-white hover:bg-gray-700 whitespace-nowrap">2. Uji Coba</button></div>
                                            </div>
                                        </form>
                                    <?php endif; ?>

                                    <div class="mt-2">
                                    <?php
                                    // Logika untuk menampilkan hasil atau pesan error
                                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                                        if(isset($_POST['ddl_sql']) && base64_decode($_POST['ddl_sql']) == $q['sql']) {
                                            if ($mysqli->multi_query($q['sql'])) {
                                                while ($mysqli->more_results() && $mysqli->next_result()) {;}
                                                echo "<div class='alert alert-success mt-2'>Objek berhasil dibuat/diperbarui.</div>";
                                            } else { echo "<div class='alert alert-danger mt-2'>Error: " . $mysqli->error . "</div>"; }
                                        }
                                        elseif (isset($_POST['test_query']) && $_POST['query_index'] == $idx) {
                                            if ($_POST['test_query'] == 'view') {
                                                display_query_results($mysqli->query("SELECT * FROM `{$q['view_name']}` LIMIT 5"));
                                            }
                                            elseif ($_POST['test_query'] == 'trigger') {
                                                // Test case untuk trigger stok
                                                $initial_status = ($q['trigger_name'] == 'trg_UpdateStokSaatDisetujui') ? 'Menunggu Persetujuan' : 'dipinjam';
                                                $final_status = ($q['trigger_name'] == 'trg_UpdateStokSaatDisetujui') ? 'dipinjam' : 'Dikembalikan';
                                                $loan_q = $mysqli->query("SELECT p.ID_peminjaman, pa.ID_alat FROM Peminjaman p JOIN Peminjaman_Alat pa ON p.ID_peminjaman = pa.ID_peminjaman WHERE p.status = '{$initial_status}' LIMIT 1");
                                                if ($loan_q && $loan_q->num_rows > 0) {
                                                    $loan = $loan_q->fetch_assoc();
                                                    $stok_q_before = $mysqli->query("SELECT stok_alat FROM Alat WHERE ID_alat = '{$loan['ID_alat']}'");
                                                    $stok_before = $stok_q_before->fetch_assoc()['stok_alat'];
                                                    $mysqli->query("UPDATE Peminjaman SET status = '{$final_status}' WHERE ID_peminjaman = '{$loan['ID_peminjaman']}'");
                                                    $stok_q_after = $mysqli->query("SELECT stok_alat FROM Alat WHERE ID_alat = '{$loan['ID_alat']}'");
                                                    $stok_after = $stok_q_after->fetch_assoc()['stok_alat'];
                                                    $action_text = ($final_status == 'dipinjam') ? "mengurangi" : "mengembalikan";
                                                    echo "<div class='mt-4 p-4 bg-green-50 border border-green-200 rounded-md text-sm'><p>Pengujian untuk alat <strong>{$loan['ID_alat']}</strong>:</p><ul class='list-disc list-inside mt-2'><li>Stok sebelum: <strong>{$stok_before}</strong></li><li>Aksi: Status diubah menjadi '{$final_status}'.</li><li>Stok sesudah: <strong>{$stok_after}</strong></li></ul><p class='mt-2 font-semibold text-green-700'>Bukti: Trigger berhasil {$action_text} stok!</p></div>";
                                                } else { echo "<div class='alert alert-warning mt-2'>Gagal menguji: Tidak ada data peminjaman dengan status '{$initial_status}' untuk dijadikan sampel.</div>"; }
                                            }
                                            elseif ($_POST['test_query'] == 'function') {
                                                $call_sql = $q['call']; $call_params = []; $call_types = $q['param_type'];
                                                if(isset($q['inputs'])) {
                                                    foreach($q['inputs'] as $input) { $call_params[] = $_POST[$input['name']]; }
                                                } else { $call_params[] = $_POST[$q['input']['name']]; }

                                                $stmt = $mysqli->prepare($call_sql);
                                                if($stmt) {
                                                    $stmt->bind_param($call_types, ...$call_params);
                                                    $stmt->execute();
                                                    display_query_results($stmt->get_result());
                                                    $stmt->close();
                                                } else { echo "<div class='alert alert-danger mt-2'>Gagal mempersiapkan query. Error: ".$mysqli->error."</div>"; }
                                            }
                                        }
                                    }
                                    ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.querySelectorAll('.accordion-header').forEach(header => {
    header.addEventListener('click', () => {
        const accordionItem = header.parentElement;
        if (!accordionItem.classList.contains('active')) {
            document.querySelectorAll('.accordion-item.active').forEach(item => { item.classList.remove('active'); });
            accordionItem.classList.add('active');
        } else {
            accordionItem.classList.remove('active');
        }
    });
});
</script>