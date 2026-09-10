<?php
require_once __DIR__ . '/../template/auth.php';
require_authenticated();

include "../koneksi.php";

$id_jenis = isset($_GET['id_jenis']) ? intval($_GET['id_jenis']) : 0;

echo '<option value="">-- Pilih Mesin --</option>';

if ($id_jenis > 0) {
    $query = mysqli_query($conn, "SELECT id, nama_mesin FROM mesin WHERE id_jenis_mesin = $id_jenis ORDER BY nama_mesin ASC");
    while ($row = mysqli_fetch_assoc($query)) {
        echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nama_mesin']) . '</option>';
    }
}
?>