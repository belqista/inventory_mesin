<?php
require_once __DIR__ . '/../template/auth.php';
require_authenticated();

include "../koneksi.php";

$id_area = isset($_GET['id_area']) ? intval($_GET['id_area']) : 0;

echo '<option value="">-- Pilih Jenis Mesin --</option>';

if ($id_area > 0) {
    $query = mysqli_query($conn, "SELECT id, nama_jenis_mesin FROM jenis_mesin WHERE id_area = $id_area ORDER BY nama_jenis_mesin ASC");
    while ($row = mysqli_fetch_assoc($query)) {
        echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nama_jenis_mesin']) . '</option>';
    }
}
?>