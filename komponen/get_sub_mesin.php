<?php
require_once __DIR__ . '/../template/auth.php';
require_authenticated();

include "../koneksi.php";

$id_mesin = isset($_GET['id_mesin']) ? intval($_GET['id_mesin']) : 0;

echo '<option value="">-- Pilih Sub Mesin --</option>';

if ($id_mesin > 0) {
    $query = mysqli_query($conn, "SELECT id, nama_sub_mesin FROM sub_mesin WHERE id_mesin = $id_mesin ORDER BY nama_sub_mesin ASC");
    while ($row = mysqli_fetch_assoc($query)) {
        echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nama_sub_mesin']) . '</option>';
    }
}
?>