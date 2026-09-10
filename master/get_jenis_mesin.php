<?php
include "../koneksi.php";

$id_area = isset($_GET['id_area']) ? intval($_GET['id_area']) : 0;

echo '<option value="">-- Pilih Jenis Mesin --</option>';

if ($id_area > 0) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM jenis_mesin WHERE id_area = ? ORDER BY nama_jenis_mesin ASC");
    mysqli_stmt_bind_param($stmt, "i", $id_area);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nama_jenis_mesin']) . '</option>';
    }
    mysqli_stmt_close($stmt);
}
?>